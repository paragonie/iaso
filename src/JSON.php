<?php
declare(strict_types=1);
namespace ParagonIE\Iaso;

use ParagonIE\Iaso\Contract\TypeInterface;
use ParagonIE\Iaso\Result\Assoc;

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
     * @return array|ResultSet
     */
    public static function parse(string $json, Contract $contract = null, bool $asArray = false)
    {
        $parsed = isset($contract)
            ? static::parseWithContract($json, $contract)
            : static::parseString($json);

        if ($asArray) {
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

        return new Assoc();
    }

    /**
     * @param string $json
     * @param Contract $contract
     * @return ResultSet
     */
    protected static function parseWithContract(string $json, Contract $contract): ResultSet
    {
        $parser = static::getParser();

        return new Assoc();
    }

    /**
     *
     */
    public static function getParser(): Parser
    {
        if (!isset(self::$parser)) {
            self::$parser = new Parser;
        }
        return self::$parser;
    }

    /**
     * @return string
     */
    public static function getSeed(): string
    {
        if (!self::$seed) {
            self::$seed = random_bytes(\ParagonIE_Sodium_Compat::CRYPTO_SHORTHASH_KEYBYTES);
        }
        return self::$seed;
    }
}
