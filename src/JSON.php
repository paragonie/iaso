<?php
declare(strict_types=1);
namespace ParagonIE\Iaso;

use ParagonIE\Iaso\Contract\Blank;
use ParagonIE\Iaso\Result\Bare;

/**
 * Class JSON
 *
 * This is just an easy public API
 *
 * @package ParagonIE\Iaso
 */
abstract class JSON
{
    /**
     * @var string
     */
    protected static $seed;

    /**
     * @var Parser
     */
    protected static $parser;

    /**
     * @param string $json            Input data
     * @param Contract|null $contract Contract object for the parser
     * @param bool $asArray           Convert objects to arrays?
     * @return mixed
     */
    public static function parse(
        string $json,
        Contract $contract = null,
        bool $asArray = false
    ) {
        $parsed = isset($contract)
            ? static::parseWithContract($json, $contract)
            : static::parseString($json);

        if ($parsed instanceof Bare) {
            return $parsed->getBareValue();
        } elseif ($asArray) {
            return $parsed->asArray(true);
        }
        return $parsed;
    }

    /**
     * @param string $json
     * @return ResultSet
     */
    protected static function parseString(string $json = ''): ResultSet
    {
        $parser = static::getParser();
        return $parser->parse($json, new Blank);
    }

    /**
     * @param string $json
     * @param Contract $contract
     * @return ResultSet
     */
    protected static function parseWithContract(
        string $json,
        Contract $contract
    ): ResultSet {
        $parser = static::getParser();
        return $parser->parse($json, $contract);
    }

    /**
     * Overloadable method for getting the parser object we desire
     */
    public static function getParser(): Parser
    {
        if (!isset(self::$parser)) {
            self::$parser = new Parser;
        }
        return self::$parser;
    }

    /**
     * Get a unique random secret once per script execution
     *
     * @return string
     */
    public static function getSeed(): string
    {
        if (!self::$seed) {
            self::$seed = \random_bytes(
                \ParagonIE_Sodium_Compat::CRYPTO_SHORTHASH_KEYBYTES
            );
        }
        return self::$seed;
    }
}
