<?php
declare(strict_types=1);
namespace ParagonIE\Iaso\Result;

use ParagonIE\Iaso\ResultSet;

/**
 * Class Bare
 * @package ParagonIE\Iaso\Result
 */
class Bare extends ResultSet
{
    /**
     * @var mixed|null
     */
    protected $realValue;

    /**
     * @var string
     */
    protected $realType;

    /**
     * Bare constructor.
     * @param mixed $value
     * @param string $type
     */
    public function __construct($value = null, $type = 'null')
    {
        $this->realValue = $value;
        $this->realType = $type;
    }

    /**
     * @return string|float|int|bool|null
     * @throws \TypeError
     */
    public function getBareValue()
    {
        switch ($this->realType) {
            case 'null':
                return null;
            case 'bool':
                return (bool) !empty($this->realValue);
            case 'numeric':
                return \strpos($this->realValue, '.') === false
                    ? (int) $this->realValue
                    : (float) $this->realValue;
            case 'int':
                return (int) $this->realValue;
            case 'float':
                return (float) $this->realValue;
            case 'string':
                return (string) $this->realValue;
            default:
                throw new \TypeError('Unrecognized bare value');
        }
    }

    /**
     * @param float|int|string|null $key
     * @return string
     * @throws \TypeError
     */
    protected function getIndex($key = 0): string
    {
        return '';
    }

    /**
     * @param array $array
     */
    protected function sort(array &$array)
    {
        // NOP
    }
}
