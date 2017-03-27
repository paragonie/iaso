<?php
declare(strict_types=1);
namespace ParagonIE\Iaso;
use ParagonIE\ConstantTime\Binary;
use ParagonIE\Iaso\Result\Assoc;
use ParagonIE\Iaso\Result\Bare;
use ParagonIE\Iaso\Result\Ordered;

/**
 * Class Parser
 * @package ParagonIE\Iaso
 */
class Parser
{
    /**
     * @param string $json
     * @param Contract $contract
     * @return ResultSet
     */
    public function parse(string $json, Contract $contract): ResultSet
    {
        $state = new ParseState(
            $json,
            $contract,
            0,
            Binary::safeStrlen($json)
        );

        while ($state->moreToRead()) {
            $state = $this->continueParsing($state);
        }
        if (empty($state->result)) {
            if (!empty($state->stack)) {
                $res = \array_pop($state->stack);
                $state->result = $res['obj'];
            }
        }
        return $state->result;
    }

    /**
     * @param ParseState $state
     * @return ParseState
     * @throws JSONError
     */
    protected function continueParsing(ParseState $state): ParseState
    {
        $chr = $state->getChar();
        switch ($chr) {
            case "\x09":
            case "\x0a":
            case "\x0d":
            case "\x20":
                // Continue on whitespace.
                while (\preg_match('#(\x09|\x0a|\x0d|\x20)#', $state->getChar())) {
                    ++$state->pos;
                }
                return $state;
            case '{':
                // We're parsing an object.
                \array_push(
                    $state->stack,
                    [
                        'type' => '{',
                        'begin' => $state->pos,
                        'end' => null,
                        'obj' => new Assoc()
                    ]
                );
                break;
            case '[':
                // We're parsing an array.
                \array_push(
                    $state->stack,
                    [
                        'type' => '[',
                        'begin' => $state->pos,
                        'end' => null,
                        'obj' => new Ordered()
                    ]
                );
                break;
            case '/*':
                // Multiline comment
                $pos = \strpos($state->data, '*/', $state->pos);
                if ($pos === false) {
                    throw new JSONError('Unclosed multiline comment');
                }
                $state->pos = $pos + 1;
                break;
            case '//':
                // Single-line comment
                $pos = \strpos($state->data, "\n", $state->pos);
                if ($pos === false) {
                    // Maybe this is before the ending?
                    $state->pos = $state->length - 2;
                }
                break;
            case ']':
                $state = $this->closeArray($state);
                break;
            case '}':
                $state = $this->closeObject($state);
                break;
            case ',':
                // We don't expect a , in the middle of an object declaration
                $soft = $state->softPop();
                if (!empty($soft['pending'])) {
                    throw new JSONError('Unexpected ,');
                }
                // Continue
                break;
            case '0':
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
            case '7':
            case '8':
            case '9':
                $state = $this->parseNumeric($state);
                break;
            case 'n':
            case 'N':
                if (\strtolower(Binary::safeSubstr($state->data, $state->pos, 4)) !== 'null') {
                    throw new JSONError('Unexpected character "' . $chr . '"');
                }
                $state = $this->parseNull($state);
                break;
            case 't':
            case 'T':
                if (\strtolower(Binary::safeSubstr($state->data, $state->pos, 4)) !== 'true') {
                    throw new JSONError('Unexpected character "' . $chr . '"');
                }
                $state = $this->parseBool($state, true);
                break;
            case 'f':
            case 'F':
                if (\strtolower(Binary::safeSubstr($state->data, $state->pos, 5)) !== 'false') {
                    throw new JSONError('Unexpected character "' . $chr . '"');
                }
                $state = $this->parseBool($state, false);
                break;
            case '"':
                // This can either be:
                // - A string (i.e. in an array)
                // - An object's key
                // - An object's value
                $state = $this->parseString($state);
                break;
            default:
                throw new JSONError('Unexpected character "' . $chr . '" (ASCII: ' . \ord($chr) . ')');
        }
        ++$state->pos;
        return $state;
    }

    /**
     * @param ParseState $state
     * @throws JSONError
     * @return ParseState
     */
    protected function closeArray(ParseState $state): ParseState
    {
        if (empty($state->stack)) {
            throw new JSONError('Cannot pop from empty stack');
        }
        // We're closing out an array
        $pop = \array_pop($state->stack);
        if (empty($pop['type'])) {
            throw new JSONError('Corrupted stack');
        }
        if ($pop['type'] !== '[') {
            throw new JSONError('Unexpected ]');
        }

        return $state->passToParent($pop['obj']);
    }

    /**
     * @param ParseState $state
     * @throws JSONError
     * @return ParseState
     */
    protected function closeObject(ParseState $state): ParseState
    {
        if (empty($state->stack)) {
            throw new JSONError('Cannot pop from empty stack');
        }
        // We're closing out an object
        $pop = \array_pop($state->stack);
        if (empty($pop['type'])) {
            throw new JSONError('Corrupted stack');
        }
        if ($pop['type'] !== '{') {
            throw new JSONError('Unexpected }');
        }

        return $state->passToParent($pop['obj']);
    }
    /**
     * @param ParseState $state
     * @param bool $expect
     * @return ParseState
     * @throws JSONError
     */
    protected function parseBool(ParseState $state, bool $expect = false): ParseState
    {
        if (empty($state->stack) && $state->pos === 0) {
            $state->pos = Binary::safeStrlen($state->data) - 1;
            $state->result = new Bare($expect, 'bool');
            return $state;
        }

        $popped = $state->softPop();
        if (empty($popped['type'])) {
            throw new JSONError('Corrupted stack');
        }

        if ($popped['type'] === '[') {
            $popped['obj'][] = $expect;
        } elseif ($popped['type'] === '{') {
            $idx = $state->getLastIndex();
            if (empty($state->stack[$idx]['pending'])) {
                // Uh oh. Dangling bool value.
                throw new JSONError('Unexpected boolean value');
            }
            $key = $state->stack[$idx]['pending'];
            $state->stack[$idx]['obj'][$key] = $expect;
            // We don't need this anymore. Unset it.
            $state->stack[$idx]['pending'] = null;
        } else {
            throw new JSONError('Unexpected parent type');
        }

        $state->pos += ($expect ? 4 : 5);
        return $state;
    }

    /**
     * @param ParseState $state
     * @return ParseState
     * @throws JSONError
     */
    protected function parseNull(ParseState $state): ParseState
    {
        if (empty($state->stack) && $state->pos === 0) {
            $state->pos = Binary::safeStrlen($state->data) - 1;
            $state->result = new Bare();
            return $state;
        }

        $popped = $state->softPop();
        if (empty($popped['type'])) {
            throw new JSONError('Corrupted stack');
        }

        if ($popped['type'] === '[') {
            $popped['obj'][] = null;
        } elseif ($popped['type'] === '{') {
            $idx = $state->getLastIndex();
            if (empty($state->stack[$idx]['pending'])) {
                // Uh oh. Dangling bool value.
                throw new JSONError('Unexpected null value');
            }
            $key = $state->stack[$idx]['pending'];
            $state->stack[$idx]['obj'][$key] = null;
            // We don't need this anymore. Unset it.
            $state->stack[$idx]['pending'] = null;
        } else {
            throw new JSONError('Unexpected parent type');
        }

        $state->pos += 4;
        return $state;
    }

    /**
     * @param ParseState $state
     * @return ParseState
     * @throws JSONError
     */
    protected function parseNumeric(ParseState $state): ParseState
    {
        $start = $pos = $state->pos;
        $period = false;
        $len = 0;
        do {
            ++$pos;
            if (!\ctype_digit($state->data[$pos])) {
                if ($state->data[$pos] === '.') {
                    // Allow only one.
                    if ($period) {
                        throw new JSONError('Unexpected period (.) character.');
                    }
                    $period = true;
                } else {
                    // Stop parsing
                    break;
                }
            }
            ++$len;
        } while ($pos < $state->length);

        $numeric = Binary::safeSubstr($state->data, $start, $len + 1);
        if ($period) {
            $result = (float) $numeric;
        } else {
            $result = (int) $numeric;
        }

        if (empty($state->stack) && $state->pos === 0) {
            $state->pos = Binary::safeStrlen($state->data) - 1;
            $state->result = new Bare($result, $period ? 'float' : 'int');
            return $state;
        }

        $popped = $state->softPop();
        if (empty($popped['type'])) {
            throw new JSONError('Corrupted stack');
        }

        if ($popped['type'] === '[') {
            $popped['obj'][] = $result;
        } elseif ($popped['type'] === '{') {
            $idx = $state->getLastIndex();
            if (empty($state->stack[$idx]['pending'])) {
                // Uh oh. Dangling numeric value.
                throw new JSONError('Unexpected numeric value');
            }
            $key = $state->stack[$idx]['pending'];
            $state->stack[$idx]['obj'][$key] = $result;
            // We don't need this anymore. Unset it.
            $state->stack[$idx]['pending'] = null;
        } else {
            throw new JSONError('Unexpected parent type');
        }

        $state->pos += $len;
        return $state;
    }

    /**
     * @param ParseState $state
     * @return ParseState
     * @throws JSONError
     */
    protected function parseString(ParseState $state): ParseState
    {
        $start = $pos = $state->pos;
        $len = 0;
        do {
            ++$pos;
            ++$len;
            $search = \strpos($state->data, '"', $pos);
            if ($search !== false) {
                $pos = $search;
                $len = $pos - $start;
            }
            $escaped = $state->data[$pos - 1] === '\\';
        } while ($escaped && $pos < $state->length);

        $idx = $state->getLastIndex();

        if (empty($state->stack) && $state->pos === 0) {
            $string = \str_replace(
                '\"',
                '"',
                Binary::safeSubstr($state->data, $start + 1, $len - 1)
            );
            $state->pos = Binary::safeStrlen($state->data) - 1;
            $state->result = new Bare($string, 'string');
            return $state;
        }
        $popped = $state->softPop();

        // This is the string we parsed.
        $string = \str_replace(
            '\"',
            '"',
            Binary::safeSubstr($state->data, $start + 1, $len - 1)
        );

        // Strip whitespace
        while (\preg_match('/[\x09\x0a\x20]/', $state->data[$pos + 1]) && $pos < $state->length) {
            ++$pos;
        }

        if ($popped['type'] === '{') {
            if (isset($popped['pending'])) {
                // We're finalizing this entry with another string.
                if ($state->data[$pos + 1] === ':') {
                    throw new JSONError('Unexpected :');
                }

                // Assign the value.
                $key = $state->stack[$idx]['pending'];
                $state->stack[$idx]['obj'][$key] = $string;

                // We don't need this anymore. Unset it.
                $state->stack[$idx]['pending'] = null;
            } else {
                // We're expecting a value for this later.
                if ($state->data[$pos + 1] !== ':') {
                    throw new JSONError('Expected ":", got "' . $state->data[$pos + 1] . '" instead.');
                }
                $state->stack[$idx]['pending'] = $string;
                ++$pos;
            }
        } elseif ($popped['type'] === '[') {
            // We don't expect key:value pairs inside of a [] array
            if ($state->data[$pos + 1] === ':') {
                throw new JSONError('Unexpected :');
            }
            $state->data[$idx]['obj'][] = $string;
        }

        $state->pos = $pos;
        return $state;
    }
}
