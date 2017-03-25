<?php
declare(strict_types=1);

namespace ParagonIE\Iaso;
use ParagonIE\ConstantTime\Base64UrlSafe;

/**
 * Class ResultSet
 * @package ParagonIE\Iaso
 */
abstract class ResultSet implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $iasoProtectedInnerResults = [];

    /**
     * Convert this ResultSet to a naked array.
     * Optionally, can be performed recursively.
     *
     * @param bool $recurse
     * @return array
     */
    public function asArray(bool $recurse = false): array
    {
        $array = [];
        foreach ($this->iasoProtectedInnerResults as $idx => $map) {
            list ($key, $value) = [$map['key'], $map['value']];
            if (($value instanceof ResultSet) && $recurse) {
                $value = $value->asArray($recurse);
            }
            $array[$key] = $value;
        }
        $this->sort($array);
        return $array;
    }

    /**
     * Does an element exist?
     *
     * @param string|int $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return \array_key_exists(
            $this->getIndex($key),
            $this->iasoProtectedInnerResults
        );
    }

    /**
     * Get an element.
     *
     * @param string|int $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        if (!$this->offsetExists($key)) {
            return null;
        }
        $idx = $this->getIndex($key);
        return $this->iasoProtectedInnerResults[$idx]['value'];
    }

    /**
     * Store an element.
     *
     * @param string|int $key
     * @param mixed      $value
     * @return mixed
     */
    public function offsetSet($key, $value)
    {
        $idx = $this->getIndex($key);
        return $this->iasoProtectedInnerResults[$idx] = [
            'key' => $key,
            'value' => $value
        ];
    }

    /**
     * Remove an entry from our collection
     *
     * @param string|int $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $idx = $this->getIndex($key);
        unset($this->iasoProtectedInnerResults[$idx]);
    }

    /**
     * Redirect __get() to offsetGet()
     *
     * @param mixed $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    /**
     * Redirect __set() to offsetSet()
     *
     * @param mixed $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    /**
     * Ensure cloned objects are behaving correctly.
     */
    public function __clone()
    {
        $newInnerResults = [];
        foreach ($this->iasoProtectedInnerResults as $idx => $map) {
            $key = $map['key'];
            $idx = $this->getIndex($key);
            $newInnerResults[$idx] = $map;
        }
        $this->iasoProtectedInnerResults = $newInnerResults;
    }

    /**
     * Sort keys sensibly. Depends on the exact ResultSet implementation.
     *
     * @param array $array
     * @return void
     */
    abstract protected function sort(array &$array);

    /**
     * Get the index to be used. Depends on the exact ResultSet implementation.
     *
     * @param string|int $key
     * @return string
     * @throws \TypeError
     */
    abstract protected function getIndex($key): string;

    /**
     * Get a collision-resistant hash for indexing array elements
     *
     * First half: SipHash of both the object hash and this array key
     * Second half: SipHash of just this array key
     *
     * @param string $key
     * @return string
     */
    protected function getHash(string $key): string
    {
        $seed = JSON::getSeed();
        $leftKey = \spl_object_hash($this) . '##' . $key;
        return \rtrim(
            Base64UrlSafe::encode(
                \ParagonIE_Sodium_Compat::crypto_shorthash($leftKey, $seed) .
                \ParagonIE_Sodium_Compat::crypto_shorthash($key, $seed)
            ),
            '='
        );
    }
}
