<?php

declare(strict_types=1);

namespace Encoding;

use MinVWS\Codable\JSON\JSONEncoder;
use PHPUnit\Framework\TestCase;

class JSONEncoderTest extends TestCase
{
    public function testEncode(): void
    {
        $encoder = new JSONEncoder();
        $data = ['a' => [5, 4, 6], 'b' => true, 'c' => 12.3, 'd' => 42];

        $this->assertEquals(
            json_encode($data),
            $encoder->encode($data)
        );

        $this->assertEquals(
            json_encode($data, JSON_PRETTY_PRINT),
            $encoder->encode($data, JSON_PRETTY_PRINT)
        );
    }
}
