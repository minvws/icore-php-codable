<?php

namespace MinVWS\Tests\Codable\Encoding;

use MinVWS\Codable\Encoding\Encoder;
use MinVWS\Tests\Codable\Shared\FruitBackedEnum;
use MinVWS\Tests\Codable\Shared\FruitUnitEnum;
use PHPUnit\Framework\TestCase;

class EnumEncoderTest extends TestCase
{
    public function testEncodeBackedEnum(): void
    {
        $encoder = new Encoder();
        $this->assertEquals(FruitBackedEnum::Apple->value, $encoder->encode(FruitBackedEnum::Apple));
        $this->assertEquals(FruitBackedEnum::Banana->value, $encoder->encode(FruitBackedEnum::Banana));
    }

    public function testEncodeUnitEnum(): void
    {
        $encoder = new Encoder();
        $this->assertEquals('Apple', $encoder->encode(FruitUnitEnum::Apple));
        $this->assertEquals('Banana', $encoder->encode(FruitUnitEnum::Banana));
    }
}
