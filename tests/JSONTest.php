<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ParagonIE\Iaso\JSON;

/**
 * Class ParserTest
 */
class JSONTest extends TestCase
{
    /**
     * @covers \ParagonIE\Iaso\JSON::parse()
     */
    public function testJSONBasic()
    {
        $this->assertSame(null, JSON::parse('null'), 'null');
        $this->assertSame(true, JSON::parse('true'), 'bool (true)');
        $this->assertSame(false, JSON::parse('false'), 'bool (false)');
        $this->assertSame('apple', JSON::parse('"apple"'), 'string');
    }
}
