<?php
declare(strict_types=1);
namespace ParagonIE\Iaso;

use ParagonIE\ConstantTime\Binary;
use ParagonIE\Iaso\Contract\Blank;

/**
 * Class ParseState
 * @package ParagonIE\Iaso
 */
class ParseState
{
    /**
     * @var Contract
     */
    public $contract;

    /**
     * @var string
     */
    public $data = '';

    /**
     * @var int
     */
    public $length = 0;

    /**
     * @var int
     */
    public $pos = 0;

    /**
     * @var ResultSet
     */
    public $result;

    /**
     * @var array<mixed, array<mixed, mixed>>
     */
    public $stack = [];

    /**
     * @var int
     */
    public $start = 0;

    /**
     * ParseState constructor.
     * @param string $data
     * @param Contract|null $contract
     * @param int $start
     * @param int $len
     */
    public function __construct(
        string $data = '',
        Contract $contract = null,
        int $start = 0,
        int $len = 0
    ) {
        $this->data = $data;
        if (\is_null($contract)) {
            $contract = new Blank();
        }
        $this->contract = $contract;
        $this->start = $start;
        $this->length = $len;
    }

    /**
     * Get the current character.
     *
     * @return string
     */
    public function getChar(): string
    {
        if ($this->pos >= Binary::safeStrlen($this->data)) {
            return '';
        }
        return $this->data[$this->pos];
    }

    /**
     * @return bool
     */
    public function moreToRead(): bool
    {
        return $this->pos < $this->length;
    }

    /**
     * @return int
     */
    public function getLastIndex(): int
    {
        $keys = \array_keys($this->stack);
        return (int) \array_pop($keys);
    }

    /**
     * @param ResultSet $pop
     * @return ParseState
     * @throws JSONError
     */
    public function passToParent(ResultSet $pop): ParseState
    {
        if (empty($this->stack)) {
            // We're done parsing!
            $this->result = $pop;
            return $this;
        }

        $idx = $this->getLastIndex();
        if ($this->stack[$idx]['type'] === '[') {
            // Just append
            $this->stack[$idx]['obj'][] = clone $pop;
        } elseif ($this->stack[$idx]['type'] === '{') {
            // Specify its key value.
            if (empty($this->stack[$idx]['pending'])) {
                throw new JSONError('Unexpected value (no key)');
            }
            $key = $this->stack[$idx]['pending'];
            $this->stack[$idx]['obj'][$key] = clone $pop;
            unset($this->stack[$idx]['pending']);
        }
        return $this;
    }

    /**
     * Return the latest element of the array without removing it.
     *
     * @return array
     */
    public function softPop(): array
    {
        return $this->stack[$this->getLastIndex()];
    }
}
