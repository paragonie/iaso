<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Class ParserTest
 */
class ParserTest extends TestCase
{
    /**
     * @covers \ParagonIE\Iaso\Parser::parse()
     */
    public function testParseBasic()
    {
        $parser = new ParagonIE\Iaso\Parser();
        $blank = new ParagonIE\Iaso\Contract\Blank();

        $result = $parser->parse(
            '{"apple":123,"boy":[1,2,3],"cat":{"a":1,"b":2,"c":3}}',
            $blank
        );
        $this->assertSame(
            ['apple' => 123, 'boy' => [1, 2, 3], 'cat' => ['a' => 1, 'b' => 2, 'c' => 3]],
            $result->asArray(true),
            'Failing with no whitespace'
        );

        $result = $parser->parse(
            '{"apple":123,"boy":[1, 2, 3 ],"cat":{"a":1,"b":2,"c":3}}',
            $blank
        );
        $this->assertSame(
            ['apple' => 123, 'boy' => [1, 2, 3], 'cat' => ['a' => 1, 'b' => 2, 'c' => 3]],
            $result->asArray(true),
            'Failing with some whitespace'
        );

        $result = $parser->parse(
            '{
                      "apple": 123,
                      "boy": [
                          1,
                          2,
                          3
                      ],
                      "cat": {
                          "a": 1,
                          "b": 2,
                          "c": 3.14
                      },
                      "dhole": true,
                      "dog": false,
                      "echo": null
                  }',
            $blank
        );
        $this->assertSame(
            [
                'apple' => 123,
                'boy' => [1, 2, 3],
                'cat' => ['a' => 1, 'b' => 2, 'c' => 3.14],
                'dhole' => true,
                'dog' => false,
                'echo' => null
            ],
            $result->asArray(true),
            'Failing while prettified'
        );
    }
}
