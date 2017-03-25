<?php
declare(strict_types=1);
namespace ParagonIE\Iaso\Result;

use ParagonIE\Iaso\ResultSet;

/**
 * Class Ordered
 * @package ParagonIE\Iaso\Result
 */
class Ordered extends ResultSet
{
    /**
     * @var int
     */
    protected $iterator = 0;

    /**
     * Store an element.
     *
     * @param int|null $key
     * @param mixed      $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if ($key === null) {
            $key = $this->iterator++;
        } elseif ($key >= $this->iterator) {
            $this->iterator = $key + 1;
        }
        parent::offsetSet($key, $value);
    }

    /**
     * @param float|int|string|null $key
     * @return string
     * @throws \TypeError
     */
    protected function getIndex($key = 0): string
    {
        if (\is_float($key)) {
            $key = (int) $key;
        }
        if (\is_int($key)) {
            $str = '';
            for ($i = 0; $i < PHP_INT_SIZE; ++$i) {
                $str .= \pack(
                    'C',
                    ($key >> ($i << 3)) & 0xff
                );
            }
            $key = $str;
        }
        if (!\is_string($key)) {
            throw new \TypeError('Key must be a string, ' . \gettype($key) . ' given.');
        }
        return $this->getHash($key);
    }
    /**
     * @param array $array
     */
    protected function sort(array &$array)
    {
        \ksort($array, SORT_NUMERIC);
    }
}
