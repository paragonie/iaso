<?php
declare(strict_types=1);
namespace ParagonIE\Iaso\Result;

use ParagonIE\Iaso\ResultSet;

/**
 * Class Assoc
 * @package ParagonIE\Iaso\Result
 */
class Assoc extends ResultSet
{
    /**
     * @param string $key
     * @return string
     * @throws \TypeError
     */
    protected function getIndex($key = ''): string
    {
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
        \ksort($array, SORT_STRING);
    }
}
