<?php
declare(strict_types=1);

namespace MinVWS\Tests\Codable\Decoding;

use Generator;
use MinVWS\Codable\Decoding\Decoder;
use MinVWS\Codable\Exceptions\ValueNotFoundException;
use MinVWS\Tests\Codable\Traits\WithFaker;
use PHPUnit\Framework\TestCase;

class PrimitiveDecoderTest extends TestCase
{
    use WithFaker;

    public static function primitiveTypesWithoutNullProvider(): Generator
    {
        yield 'string' => ['string'];
        yield 'bool' => ['string'];
        yield 'int' => ['int'];
        yield 'float' => ['float'];
    }

    public static function primitiveTypesProvider(): Generator
    {
        foreach (self::primitiveTypesWithoutNullProvider() as $key => $value) {
            yield $key => $value;
        }
        yield 'null' => ['null'];
    }

    public static function primitiveValuesWithoutNullProvider(): Generator
    {
        yield 'bool-true' => ['bool', true];
        yield 'bool-false' => ['bool', false];
        yield 'positive-int' => ['int', self::faker()->numberBetween()];
        yield 'negative-int' => ['int', self::faker()->numberBetween(PHP_INT_MIN, -1)];
        yield 'positive-float' => ['float', self::faker()->randomFloat()];
        yield 'negative-float' => ['float', self::faker()->randomFloat(PHP_FLOAT_MIN, -0.1)];
        yield ['string', self::faker()->text];

    }

    public static function primitiveValuesProvider(): Generator
    {
        foreach (self::primitiveValuesWithoutNullProvider() as $key => $value) {
            yield $key => $value;
        }
        yield 'null' => ['null', null];
    }

    /**
     * @dataProvider primitiveValuesProvider
     */
    public function testDecode(string $type, mixed $value): void
    {
        $decoder = new Decoder();
        $container = $decoder->decode($value);
        $this->assertEquals($value, $container->decode($type));
        $method = 'decode' . ucfirst($type);
        $this->assertEquals($value, $container->$method());
    }

    /**
     * @dataProvider primitiveValuesProvider
     */
    public function testNestedDecode(string $type, mixed $value): void
    {
        $decoder = new Decoder();
        $container = $decoder->decode(['value' => $value]);
        $this->assertEquals($value, $container->{'value'}->decode($type));
        $method = 'decode' . ucfirst($type);
        $this->assertEquals($value, $container->{'value'}->$method());
    }

    /**
     * @dataProvider primitiveValuesProvider
     */
    public function testDecodeIfExists(string $type, mixed $value): void
    {
        $decoder = new Decoder();
        $container = $decoder->decode($value);
        $this->assertEquals($value, $container->decodeIfExists($type));
        $method = 'decode' . ucfirst($type) . 'IfExists';
        $this->assertEquals($value, $container->$method());
    }

    /**
     * @dataProvider primitiveValuesProvider
     */
    public function testNestedDecodeIfExists(string $type, mixed $value): void
    {
        $decoder = new Decoder();
        $container = $decoder->decode(['value' => $value]);
        $this->assertEquals($value, $container->{'value'}->decodeIfExists($type));
        $method = 'decode' . ucfirst($type) . 'IfExists';
        $this->assertEquals($value, $container->{'value'}->$method());
    }

    /**
     * @dataProvider primitiveTypesProvider
     */
    public function testNestedDecodeIfExistsReturnsNullIfNotExists(string $type): void
    {
        $decoder = new Decoder();
        $container = $decoder->decode([]);
        $this->assertNull($container->{'value'}->decodeIfExists($type));
        $method = 'decode' . ucfirst($type) . 'IfExists';
        $this->assertNull($container->{'value'}->$method());
    }

    /**
     * @dataProvider primitiveValuesWithoutNullProvider
     */
    public function testNestedDecodeIfExistsThrowsExceptionIfNotPresent(string $type, mixed $value): void
    {
        $decoder = new Decoder();
        $container = $decoder->decode(['value' => null]);
        $this->expectException(ValueNotFoundException::class);
        $this->assertEquals($value, $container->{'value'}->decodeIfExists($type));
        $method = 'decode' . ucfirst($type) . 'IfExists';
        $this->assertEquals($value, $container->{'value'}->$method());
    }

    /**
     * @dataProvider primitiveValuesWithoutNullProvider
     */
    public function testNestedDecodeTypeIfExistsThrowsExceptionIfNotPresent(string $type, mixed $value): void
    {
        $decoder = new Decoder();
        $container = $decoder->decode(['value' => null]);
        $this->expectException(ValueNotFoundException::class);
        $method = 'decode' . ucfirst($type) . 'IfExists';
        $this->assertEquals($value, $container->{'value'}->$method());
    }

    /**
     * @dataProvider primitiveValuesWithoutNullProvider
     */
    public function testDecodeIfPresent(string $type, mixed $value): void
    {
        $decoder = new Decoder();
        $container = $decoder->decode($value);
        $this->assertEquals($value, $container->decodeIfPresent($type));
        $method = 'decode' . ucfirst($type) . 'IfPresent';
        $this->assertEquals($value, $container->$method());
    }

    /**
     * @dataProvider primitiveValuesWithoutNullProvider
     */
    public function testNestedDecodeIfPresent(string $type, mixed $value): void
    {
        $decoder = new Decoder();
        $container = $decoder->decode(['value' => $value]);
        $this->assertEquals($value, $container->{'value'}->decodeIfPresent($type));
        $method = 'decode' . ucfirst($type) . 'IfPresent';
        $this->assertEquals($value, $container->{'value'}->$method());
    }

    /**
     * @dataProvider primitiveTypesWithoutNullProvider
     */
    public function testDecodeIfPresentReturnsNullIfNotPresent(string $type): void
    {
        $decoder = new Decoder();
        $container = $decoder->decode(null);
        $this->assertNull($container->decodeIfPresent($type));
        $method = 'decode' . ucfirst($type) . 'IfPresent';
        $this->assertNull($container->$method());
    }

    /**
     * @dataProvider primitiveTypesWithoutNullProvider
     */
    public function testNestedDecodeIfPresentReturnsNullIfNotPresent(string $type): void
    {
        $decoder = new Decoder();
        $container = $decoder->decode(['value' => null]);
        $this->assertNull($container->{'value'}->decodeIfPresent($type));
        $method = 'decode' . ucfirst($type) . 'IfPresent';
        $this->assertNull($container->{'value'}->$method());
    }
}