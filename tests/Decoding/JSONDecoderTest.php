<?php

declare(strict_types=1);

namespace Decoding;

use MinVWS\Codable\JSON\JSONDecoder;
use PHPUnit\Framework\TestCase;

class JSONDecoderTest extends TestCase
{
    public function testDecode(): void
    {
        $decoder = new JSONDecoder();

        $data = ['a' => [5, 4, 6], 'b' => true, 'c' => 12.3, 'd' => 42];
        $json = json_encode($data);
        $this->assertIsString($json);

        $this->assertEquals(
            json_decode($json),
            $decoder->decode($json)->decode()
        );

        $this->assertIsObject($decoder->decode($json)->decode());

        $this->assertEquals(
            json_decode($json, associative: true),
            $decoder->decode($json, associative: true)->decode()
        );

        $this->assertIsArray($decoder->decode($json, true)->decode());


        $this->assertEquals(
            json_decode($json, flags: JSON_OBJECT_AS_ARRAY),
            $decoder->decode($json, flags: JSON_OBJECT_AS_ARRAY)->decode()
        );

        $this->assertIsArray($decoder->decode($json, flags: JSON_OBJECT_AS_ARRAY)->decode());
    }
}
