<?php

namespace MinVWS\Tests\Codable\Decoding;

use MinVWS\Codable\Decoding\Decoder;
use MinVWS\Codable\Exceptions\InvalidValueException;
use MinVWS\Codable\Exceptions\ValueNotFoundException;
use MinVWS\Codable\Exceptions\ValueTypeMismatchException;
use MinVWS\Tests\Codable\Shared\Fruit;
use MinVWS\Tests\Codable\Shared\Vegetable;
use PHPUnit\Framework\TestCase;
use Throwable;

class EnumDecoderTest extends TestCase
{
    public function testDecodeBackedEnum(): void
    {
        $container = (new Decoder())->decode('apple');

        $value = $container->decodeEnum(Fruit::class);
        $this->assertEquals(Fruit::Apple, $value);

        $value = $container->decodeObject(Fruit::class);
        $this->assertEquals(Fruit::Apple, $value);

        $value = $container->decodeObject(Fruit::class);
        $this->assertEquals(Fruit::Apple, $value);
    }

    public static function invalidValuesForBackedEnumProvider(): array
    {
        return [
            'invalid-value' => ['Apple', InvalidValueException::class],
            'type-mismatch-int' => [1, ValueTypeMismatchException::class],
            'type-mismatch-array' => [[], ValueTypeMismatchException::class],
            'value-not-found' => [null, ValueNotFoundException::class]
        ];
    }

    /**
     * @param class-string<Throwable> $expectedException
     * @dataProvider invalidValuesForBackedEnumProvider
     */
    public function testDecodeBackedEnumShouldThrowAnExceptionForInvalidValues(mixed $value, string $expectedException): void
    {
        $this->expectException($expectedException);
        $decoder = new Decoder();
        $decoder->decode($value)->decodeEnum(Fruit::class);
    }

    public function testDecodeUnitEnum(): void
    {
        $container = (new Decoder())->decode('Tomato');

        $value = $container->decodeEnum(Vegetable::class);
        $this->assertEquals(Vegetable::Tomato, $value);

        $value = $container->decodeObject(Vegetable::class);
        $this->assertEquals(Vegetable::Tomato, $value);

        $value = $container->decodeObject(Vegetable::class);
        $this->assertEquals(Vegetable::Tomato, $value);
    }

    public static function invalidValuesForUnitEnumProvider(): array
    {
        return [
            'invalid-value' => ['tomato', InvalidValueException::class],
            'type-mismatch-int' => [1, ValueTypeMismatchException::class],
            'type-mismatch-array' => [[], ValueTypeMismatchException::class],
            'value-not-found' => [null, ValueNotFoundException::class]
        ];
    }

    /**
     * @param class-string<Throwable> $expectedException
     * @dataProvider invalidValuesForUnitEnumProvider
     */
    public function testDecodeUnitEnumShouldThrowAnExceptionForInvalidValues(mixed $value, string $expectedException): void
    {
        $this->expectException($expectedException);
        $decoder = new Decoder();
        $decoder->decode($value)->decodeEnum(Vegetable::class);
    }
}
