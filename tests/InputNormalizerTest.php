<?php

namespace EasyMerge2pdfTests;

use EasyMerge2pdf\InputNormalizer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class InputNormalizerTest extends TestCase
{
    const INPUT_FILE = 'f.pdf';

    public function dataProvider(): array
    {
        return [
            ['1,2', '~1,2'],
            ['1-3', '~1,2,3'],
            ['1,2,4-6,8', '~1,2,4,5,6,8'],
            ['', ''],
        ];
    }

    /**
     * @dataProvider dataProvider
     * @param $pages
     * @param $expected
     */
    public function testInput($pages, $expected)
    {
        $this->assertEquals(InputNormalizer::normalize(self::INPUT_FILE, $pages), self::INPUT_FILE . $expected);
    }

    public function testShouldThroughExceptionForInvalidPage()
    {
        $this->expectException(InvalidArgumentException::class);
        InputNormalizer::normalize(self::INPUT_FILE, '1-2-3');
    }
}
