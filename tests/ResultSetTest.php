<?php

use ParagonIE\Iaso\Result\{Assoc, Ordered};
use ParagonIE\Iaso\ResultSet;
use PHPUnit\Framework\TestCase;

/**
 * Class ResultSetTest
 *
 * @covers Assoc
 * @covers Ordered
 * @covers ResultSet
 */
class ResultSetTest extends TestCase
{
    /**
     * @covers ResultSet::__set()
     * @covers ResultSet::__get()
     * @covers ResultSet::offsetGet()
     * @covers ResultSet::offsetSet()
     */
    public function testSetGet()
    {
        $result = new Assoc();
        $result->apple = 'test';
        $result['boy'] = 'test123';

        $this->assertSame('test', $result->apple, '__get() is misbehaving');
        $this->assertSame('test', $result['apple'], 'ArrayAccess is misbehaving');
        $this->assertSame('test123', $result->boy, '__get() is misbehaving');
        $this->assertSame('test123', $result['boy'], 'ArrayAccess is misbehaving');

        $ordered = new Ordered();
        $ordered[0] = 'test';
        $ordered[] = 'test2';
        $ordered[] = 'test3';
        $ordered[9] = 'test4';
        $ordered[] = 'test5';

        $this->assertSame('test', $ordered[0]);
        $this->assertSame('test2', $ordered[1]);
        $this->assertSame('test3', $ordered[2]);
        $this->assertSame('test4', $ordered[9]);
        $this->assertSame('test5', $ordered[10]);

    }

    /**
     * @covers ResultSet::asArray()
     * @covers ResultSet::__clone()
     */
    public function testAsArrayWithClone()
    {
        $result = new Assoc();
        $result->apple = 'test';
        $result->boy = 'test123';
        $result->copy = clone $result;

        $this->assertSame(
            [
                'apple' => 'test',
                'boy' => 'test123',
                'copy' => ['apple' => 'test', 'boy' => 'test123'],
            ],
            $result->asArray(true),
            'Basic clone and asArray() test is failing.'
        );

        $this->assertTrue(
            $result->asArray(false)['copy'] instanceof ResultSet,
            'Non-recursion is being ignored.'
        );
    }
}
