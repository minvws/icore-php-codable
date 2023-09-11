<?php

namespace MinVWS\Tests\Codable\Encoding;

use MinVWS\Codable\Encoding\Encoder;
use MinVWS\Tests\Codable\Shared\Fruit;
use MinVWS\Tests\Codable\Shared\Vegetable;
use PHPUnit\Framework\TestCase;

class EnumEncoderTest extends TestCase
{
    public function testEncodeBackedEnum(): void
    {
        $encoder = new Encoder();
        $this->assertEquals(Fruit::Apple->value, $encoder->encode(Fruit::Apple));
        $this->assertEquals(Fruit::Banana->value, $encoder->encode(Fruit::Banana));
    }

    public function testEncodeUnitEnum(): void
    {
        $encoder = new Encoder();
        $this->assertEquals('Lettuce', $encoder->encode(Vegetable::Lettuce));
        $this->assertEquals('Tomato', $encoder->encode(Vegetable::Tomato));
    }
}
