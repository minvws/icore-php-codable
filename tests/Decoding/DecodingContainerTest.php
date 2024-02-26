<?php

declare(strict_types=1);

namespace MinVWS\Tests\Codable\Decoding;

use DateTime;
use DateTimeImmutable;
use Generator;
use MinVWS\Codable\Decoding\Decoder;
use MinVWS\Codable\Decoding\DecodingContainer;
use MinVWS\Codable\Exceptions\InvalidValueException;
use MinVWS\Codable\Exceptions\KeyTypeMismatchException;
use MinVWS\Codable\Exceptions\ValueNotFoundException;
use MinVWS\Codable\Exceptions\ValueTypeMismatchException;
use MinVWS\Tests\Codable\Shared\Fruit;
use MinVWS\Tests\Codable\Shared\FruitBasket;
use MinVWS\Tests\Codable\Shared\Person;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class DecodingContainerTest extends TestCase
{
    private Decoder $decoder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->decoder = new Decoder();
    }

    private static function provide(
        string $method,
        array $methodArgs = [],
        mixed $inputValue = null,
        mixed ...$args
    ): Generator {
        yield sprintf(
            '%s(%s) - %s',
            $method,
            implode(',', $methodArgs),
            var_export($inputValue, true)
        ) => [$method, $methodArgs, $inputValue, ...$args];
    }

    private static function provideProduct(
        string $method,
        array $methodArgs = [],
        array $inputValues = [],
        mixed ...$args
    ): Generator {
        foreach ($inputValues as $inputValue) {
            yield from self::provide($method, $methodArgs, $inputValue, ...$args);
        }
    }

    public static function nullProvider(): Generator
    {
        yield from self::provide(method: 'decodeNull', inputValue: null, expectedOutputValue: null);
        yield from self::provide(method: 'decodeNullIfExists', inputValue: null, expectedOutputValue: null);
        yield from self::provide(method: 'decode', inputValue: null, expectedOutputValue: null);
        yield from self::provide(method: 'decode', methodArgs: ['null'], inputValue: null, expectedOutputValue: null);
    }

    public static function boolProvider(): Generator
    {
        yield from self::provide(method: 'decodeBool', inputValue: true, expectedOutputValue: true);
        yield from self::provide(method: 'decodeBool', inputValue: false, expectedOutputValue: false);
        yield from self::provide(method: 'decodeBoolIfExists', inputValue: true, expectedOutputValue: true);
        yield from self::provide(method: 'decodeBoolIfExists', inputValue: false, expectedOutputValue: false);
        yield from self::provide(method: 'decodeBoolIfPresent', inputValue: true, expectedOutputValue: true);
        yield from self::provide(method: 'decodeBoolIfPresent', inputValue: false, expectedOutputValue: false);
        yield from self::provide(method: 'decodeBoolIfPresent', inputValue: null, expectedOutputValue: null);
        yield from self::provide(method: 'decode', inputValue: true, expectedOutputValue: true);
        yield from self::provide(method: 'decode', inputValue: false, expectedOutputValue: false);
        yield from self::provide(method: 'decode', methodArgs: ['bool'], inputValue: true, expectedOutputValue: true);
        yield from self::provide(method: 'decode', methodArgs: ['bool'], inputValue: false, expectedOutputValue: false);
        yield from self::provide(method: 'decode', methodArgs: ['boolean'], inputValue: true, expectedOutputValue: true);
        yield from self::provide(method: 'decode', methodArgs: ['boolean'], inputValue: false, expectedOutputValue: false);
    }

    public static function intProvider(): Generator
    {
        yield from self::provide(method: 'decodeInt', inputValue: 42, expectedOutputValue: 42);
        yield from self::provide(method: 'decodeIntIfExists', inputValue: 42, expectedOutputValue: 42);
        yield from self::provide(method: 'decodeIntIfPresent', inputValue: 42, expectedOutputValue: 42);
        yield from self::provide(method: 'decodeIntIfPresent', inputValue: null, expectedOutputValue: null);
        yield from self::provide(method: 'decode', inputValue: 42, expectedOutputValue: 42);
        yield from self::provide(method: 'decode', methodArgs: ['int'], inputValue: 42, expectedOutputValue: 42);
        yield from self::provide(method: 'decode', methodArgs: ['integer'], inputValue: 42, expectedOutputValue: 42);
        yield from self::provide(method: 'decode', methodArgs: ['long'], inputValue: 42, expectedOutputValue: 42);
    }

    public static function floatProvider(): Generator
    {
        yield from self::provide(method: 'decodeFloat', inputValue: 4.2, expectedOutputValue: 4.2);
        yield from self::provide(method: 'decodeFloatIfExists', inputValue: 4.2, expectedOutputValue: 4.2);
        yield from self::provide(method: 'decodeFloatIfPresent', inputValue: 4.2, expectedOutputValue: 4.2);
        yield from self::provide(method: 'decodeFloatIfPresent', inputValue: null, expectedOutputValue: null);
        yield from self::provide(method: 'decode', inputValue: 4.2, expectedOutputValue: 4.2);
        yield from self::provide(method: 'decode', methodArgs: ['float'], inputValue: 4.2, expectedOutputValue: 4.2);
        yield from self::provide(method: 'decode', methodArgs: ['double'], inputValue: 4.2, expectedOutputValue: 4.2);
    }

    public static function stringProvider(): Generator
    {
        yield from self::provide(method: 'decodeString', inputValue: 'string', expectedOutputValue: 'string');
        yield from self::provide(method: 'decodeStringIfExists', inputValue: 'string', expectedOutputValue: 'string');
        yield from self::provide(method: 'decodeStringIfPresent', inputValue: 'string', expectedOutputValue: 'string');
        yield from self::provide(method: 'decodeStringIfPresent', inputValue: null, expectedOutputValue: null);
        yield from self::provide(method: 'decode', inputValue: 'string', expectedOutputValue: 'string');
        yield from self::provide(method: 'decode', methodArgs: ['string'], inputValue: 'string', expectedOutputValue: 'string');
    }

    public static function arrayProvider(): Generator
    {
        yield from self::provide(method: 'decodeArray', inputValue: ['a' => 1, 2], expectedOutputValue: ['a' => 1, 2]);
        yield from self::provide(method: 'decodeArray', inputValue: (object)['x' => 1], expectedOutputValue: ['x' => 1]);
        yield from self::provide(method: 'decodeArrayIfExists', inputValue: ['a' => 1, 2], expectedOutputValue: ['a' => 1, 2]);
        yield from self::provide(method: 'decodeArrayIfPresent', inputValue: ['a' => 1, 2], expectedOutputValue: ['a' => 1, 2]);
        yield from self::provide(method: 'decodeArrayIfPresent', inputValue: null, expectedOutputValue: null);
        yield from self::provide(method: 'decode', inputValue: ['a' => 1, 2], expectedOutputValue: ['a' => 1, 2]);
        yield from self::provide(method: 'decode', methodArgs: ['array'], inputValue: ['a' => 1, 2], expectedOutputValue: ['a' => 1, 2]);
    }

    public static function objectProvider(): Generator
    {
        yield from self::provide(method: 'decodeObject', inputValue: (object)['x' => 1], expectedOutputValue: (object)['x' => 1]);
        yield from self::provide(method: 'decodeObjectIfPresent', inputValue: null, expectedOutputValue: null);
        yield from self::provide(method: 'decode', inputValue: (object)['x' => 1], expectedOutputValue: (object)['x' => 1]);
        yield from self::provide(method: 'decode', methodArgs: ['object'], inputValue: (object)['x' => 1], expectedOutputValue: (object)['x' => 1]);
    }

    #[DataProvider('nullProvider')]
    #[DataProvider('boolProvider')]
    #[DataProvider('intProvider')]
    #[DataProvider('floatProvider')]
    #[DataProvider('stringProvider')]
    #[DataProvider('arrayProvider')]
    #[DataProvider('objectProvider')]
    public function testDecode(string $method, array $methodArgs, mixed $inputValue, mixed $expectedOutputValue): void
    {
        $container = $this->decoder->decode($inputValue);
        $output = $container->$method(...$methodArgs);
        $this->assertEquals($expectedOutputValue, $output);
    }

    public static function nullValueTypeMismatchProvider(): Generator
    {
        $inputValues = [
            false,
            true,
            42,
            12.3,
            'string',
            [42],
            new stdClass()
        ];

        yield from self::provideProduct(method: 'decodeNull', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decodeNullIfExists', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decode', methodArgs: ['null'], inputValues: $inputValues);
    }

    public static function boolValueTypeMismatchProvider(): Generator
    {
        $inputValues = [
            42,
            12.3,
            'string',
            [42],
            new stdClass()
        ];

        yield from self::provideProduct(method: 'decodeBool', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decodeBoolIfExists', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decodeBoolIfPresent', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decode', methodArgs: ['bool'], inputValues: $inputValues);
        yield from self::provideProduct(method: 'decode', methodArgs: ['boolean'], inputValues: $inputValues);
    }

    public static function intValueTypeMismatchProvider(): Generator
    {
        $inputValues = [
            true,
            12.3,
            'string',
            [42],
            new stdClass()
        ];

        yield from self::provideProduct(method: 'decodeInt', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decodeIntIfExists', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decodeIntIfPresent', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decode', methodArgs: ['int'], inputValues: $inputValues);
        yield from self::provideProduct(method: 'decode', methodArgs: ['integer'], inputValues: $inputValues);
        yield from self::provideProduct(method: 'decode', methodArgs: ['long'], inputValues: $inputValues);
    }

    public static function floatValueTypeMismatchProvider(): Generator
    {
        $inputValues = [
            true,
            42,
            'string',
            [42],
            new stdClass()
        ];

        yield from self::provideProduct(method: 'decodeFloat', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decodeFloatIfExists', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decodeFloatIfPresent', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decode', methodArgs: ['float'], inputValues: $inputValues);
        yield from self::provideProduct(method: 'decode', methodArgs: ['double'], inputValues: $inputValues);
    }

    public static function stringValueTypeMismatchProvider(): Generator
    {
        $inputValues = [
            true,
            42,
            12.3,
            [42],
            new stdClass()
        ];

        yield from self::provideProduct(method: 'decodeString', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decodeStringIfExists', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decodeStringIfPresent', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decode', methodArgs: ['string'], inputValues: $inputValues);
    }

    public static function arrayValueTypeMismatchProvider(): Generator
    {
        $inputValues = [
            true,
            42,
            12.3,
            'string'
        ];

        yield from self::provideProduct(method: 'decodeArray', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decodeArrayIfExists', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decodeArrayIfPresent', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decode', methodArgs: ['array'], inputValues: $inputValues);
    }

    public static function objectValueTypeMismatchProvider(): Generator
    {
        $inputValues = [
            true,
            42,
            12.3,
            'string'
        ];

        yield from self::provideProduct(method: 'decodeObject', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decodeObjectIfExists', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decodeObjectIfPresent', inputValues: $inputValues);
        yield from self::provideProduct(method: 'decode', methodArgs: ['object'], inputValues: $inputValues);
    }

    #[DataProvider('nullValueTypeMismatchProvider')]
    #[DataProvider('boolValueTypeMismatchProvider')]
    #[DataProvider('intValueTypeMismatchProvider')]
    #[DataProvider('floatValueTypeMismatchProvider')]
    #[DataProvider('arrayValueTypeMismatchProvider')]
    #[DataProvider('objectValueTypeMismatchProvider')]
    public function testDecodeShouldThrowValueTypeMismatchException(
        string $method,
        array $methodArgs,
        mixed $inputValue
    ): void {
        $this->expectException(ValueTypeMismatchException::class);
        $container = $this->decoder->decode($inputValue);
        $container->$method(...$methodArgs);
    }

    public static function boolValueNotFoundProvider(): Generator
    {
        yield from self::provide(method: 'decodeBool', inputValue: null);
        yield from self::provide(method: 'decodeBoolIfExists', inputValue: null);
        yield from self::provide(method: 'decode', methodArgs: ['bool'], inputValue: null);
        yield from self::provide(method: 'decode', methodArgs: ['boolean'], inputValue: null);
    }

    public static function intValueNotFoundProvider(): Generator
    {
        yield from self::provide(method: 'decodeInt', inputValue: null);
        yield from self::provide(method: 'decodeIntIfExists', inputValue: null);
        yield from self::provide(method: 'decode', methodArgs: ['int'], inputValue: null);
        yield from self::provide(method: 'decode', methodArgs: ['integer'], inputValue: null);
        yield from self::provide(method: 'decode', methodArgs: ['long'], inputValue: null);
    }

    public static function floatValueNotFoundProvider(): Generator
    {
        yield from self::provide(method: 'decodeFloat', inputValue: null);
        yield from self::provide(method: 'decodeFloatIfExists', inputValue: null);
        yield from self::provide(method: 'decode', methodArgs: ['float'], inputValue: null);
        yield from self::provide(method: 'decode', methodArgs: ['double'], inputValue: null);
    }

    public static function stringValueNotFoundProvider(): Generator
    {
        yield from self::provide(method: 'decodeString', inputValue: null);
        yield from self::provide(method: 'decodeStringIfExists', inputValue: null);
        yield from self::provide(method: 'decode', methodArgs: ['string'], inputValue: null);
    }

    public static function arrayValueNotFoundProvider(): Generator
    {
        yield from self::provide(method: 'decodeArray', inputValue: null);
        yield from self::provide(method: 'decodeArrayIfExists', inputValue: null);
        yield from self::provide(method: 'decode', methodArgs: ['array'], inputValue: null);
    }

    public static function objectValueNotFoundProvider(): Generator
    {
        yield from self::provide(method: 'decodeObject', inputValue: null);
        yield from self::provide(method: 'decodeObjectIfExists', inputValue: null);
        yield from self::provide(method: 'decode', methodArgs: ['object'], inputValue: null);
    }

    #[DataProvider('boolValueNotFoundProvider')]
    #[DataProvider('intValueNotFoundProvider')]
    #[DataProvider('floatValueNotFoundProvider')]
    #[DataProvider('stringValueNotFoundProvider')]
    #[DataProvider('arrayValueNotFoundProvider')]
    #[DataProvider('objectValueNotFoundProvider')]
    public function testDecodeShouldThrowValueNotFoundException(
        string $method,
        array $methodArgs,
        mixed $inputValue
    ): void {
        $this->expectException(ValueNotFoundException::class);
        $container = $this->decoder->decode($inputValue);
        $container->$method(...$methodArgs);
    }


    public function testDecodeDateTime(): void
    {
        $today = date('Y-m-d');
        $container = $this->decoder->decode($today);
        $this->assertEquals($today, $container->decodeDateTime()->format('Y-m-d'));
        $this->assertEquals($today, $container->decodeDateTimeIfExists()?->format('Y-m-d'));
        $this->assertEquals($today, $container->decodeDateTimeIfPresent()?->format('Y-m-d'));
        $this->assertInstanceOf(DateTimeImmutable::class, $container->decodeDateTime());
        $this->assertInstanceOf(DateTime::class, $container->decodeDateTime(class: DateTime::class));
    }

    private function multiLevelTestData(): array
    {
        return [
            'level1' => [
                'value' => 'value1',
                'level2' => (object)[
                    'value' => 'value2',
                    'level3' => [
                        'value' => 'value3',
                        'array' => ['a', 'b', 'c'],
                        'null' => null
                    ]
                ]
            ]
        ];
    }

    public function testExists(): void
    {
        $container = $this->decoder->decode($this->multiLevelTestData());
        $this->assertTrue($container->{'level1'}->{'value'}->exists());
        $this->assertFalse($container->{'level1'}->{'doesNotExist'}->exists());
        $this->assertTrue($container->{'level1'}->{'level2'}->{'value'}->exists());
        $this->assertTrue($container->{'level1'}->{'level2'}->{'level3'}->{'null'}->exists());
    }

    public function testIsset(): void
    {
        $container = $this->decoder->decode($this->multiLevelTestData());
        $this->assertTrue(isset($container->{'level1'}->{'value'}));
        $this->assertFalse(isset($container->{'level1'}->{'doesNotExist'}));
        $this->assertTrue(isset($container->{'level1'}->{'level2'}->{'value'}));
        $this->assertTrue(isset($container->{'level1'}->{'level2'}->{'level3'}->{'null'}));
    }

    public function testIsPresent(): void
    {
        $container = $this->decoder->decode($this->multiLevelTestData());
        $this->assertTrue($container->{'level1'}->{'value'}->isPresent());
        $this->assertFalse($container->{'level1'}->{'doesNotExist'}->isPresent());
        $this->assertTrue($container->{'level1'}->{'level2'}->{'value'}->isPresent());
        $this->assertFalse($container->{'level1'}->{'level2'}->{'level3'}->{'null'}->isPresent());
    }

    public function testNestedContainer(): void
    {
        $container = $this->decoder->decode($this->multiLevelTestData());
        $this->assertInstanceOf(DecodingContainer::class, $container->{'level1'}->{'value'});
        $this->assertInstanceOf(DecodingContainer::class, $container->{'level1'}->{'doesNotExist'});
        $this->assertEquals('value1', $container->nestedContainer('level1')->nestedContainer('value')->decodeString());
        $this->assertEquals('value1', $container->nestedContainer('level1')->{'value'}->decodeString());
    }

    public function testNestedContainerStrictness(): void
    {
        $arrayContainer = $this->decoder->decode(['a', 'b', 'c']);
        $this->assertTrue($arrayContainer[1]->exists());
        $this->assertFalse($arrayContainer['1']->exists());
        $this->assertTrue($arrayContainer->nestedContainer(1)->exists());
        $this->assertFalse($arrayContainer->nestedContainer('1', debug: true)->exists());
        $this->assertTrue($arrayContainer->nestedContainer('1', false)->exists());

        $objectContainer = $this->decoder->decode((object)['a' => 'a', 'b' => 'b', 3 => 'c']);
        $this->assertTrue($objectContainer['a']->exists());
        $this->assertTrue($objectContainer['3']->exists());
        $this->assertFalse($objectContainer[3]->exists());
        $this->assertTrue($objectContainer->nestedContainer('a')->exists());
        $this->assertTrue($objectContainer->nestedContainer('3')->exists());
        $this->assertFalse($objectContainer->nestedContainer(3)->exists());
        $this->assertTrue($objectContainer->nestedContainer(3, false)->exists());
    }

    public function testNestedContainerForPath(): void
    {
        $container = $this->decoder->decode($this->multiLevelTestData());
        $this->assertEquals('value2', $container->nestedContainerForPath(['level1', 'level2', 'value'])->decodeString());
    }

    public function testDecodeArrayKey(): void
    {
        $container = $this->decoder->decode($this->multiLevelTestData());

        $stringKeyContainer = $container->{'level1'};
        $this->assertEquals('level1', $stringKeyContainer->decodeKey());
        $this->assertEquals('level1', $stringKeyContainer->decodeStringKey());
        $this->assertEquals('level1', $stringKeyContainer->decodeStringKeyIfPresent());
        $this->assertEquals('level1', $stringKeyContainer->decodeStringKeyIfExists());

        $intKeyContainer = $container->{'level1'}->{'level2'}->{'level3'}->{'array'}[1];
        $this->assertEquals(1, $intKeyContainer->decodeKey());
        $this->assertEquals(1, $intKeyContainer->decodeIntKey());
        $this->assertEquals(1, $intKeyContainer->decodeIntKeyIfPresent());
        $this->assertEquals(1, $intKeyContainer->decodeIntKeyIfExists());

        $this->assertNull($container->{'doesNotExists'}->decodeStringKeyIfPresent());
        $this->assertNull($container->{'doesNotExists'}->decodeStringKeyIfExists());
    }

    public static function keyTypeMismatchExceptionProvider(): Generator
    {
        yield 'decodeIntKey' => [['level1'], 'decodeIntKey'];
        yield 'decodeIntKeyIfPresent' => [['level1'], 'decodeIntKeyIfPresent'];
        yield 'decodeIntKeyIfExists' => [['level1'], 'decodeIntKeyIfExists'];
        yield 'decodeStringKey' => [['level1', 'level2', 'level3', 'array', 1], 'decodeStringKey'];
        yield 'decodeStringKeyIfPresent' => [['level1', 'level2', 'level3', 'array', 1], 'decodeStringKeyIfPresent'];
        yield 'decodeStringKeyIfExists' => [['level1', 'level2', 'level3', 'array', 1], 'decodeStringKeyIfExists'];
    }

    #[DataProvider('keyTypeMismatchExceptionProvider')]
    public function testDecodeArrayKeyTypeMismatchException(array $path, string $method): void
    {
        $container = $this->decoder->decode($this->multiLevelTestData());
        $nestedContainer = $container->nestedContainerForPath($path);
        $this->assertTrue($nestedContainer->exists());
        $this->expectException(KeyTypeMismatchException::class);
        $nestedContainer->$method();
    }

    public static function typedArrayProvider(): Generator
    {
        yield 'bool-array' => [[false, true], 'bool'];
        yield 'string-array' => [['a', 'b', 'c'], 'string'];
        yield 'empty-string-array' => [[], 'string'];
        yield 'int-array' => [[1, 2, 3], 'int'];
        yield 'empty-int-array' => [[], 'int'];
        yield 'enum-array' => [[Fruit::Apple->value, Fruit::Orange->value], Fruit::class];
        yield 'empty-enum-array' => [[], Fruit::class];
        yield 'enum-array-as-strings' => [[Fruit::Apple->value, Fruit::Orange->value], 'string'];
        yield 'object-array' => [[(object)['fruits' => [Fruit::Apple->value, Fruit::Orange->value]]], FruitBasket::class];
        yield 'empty-object-array' => [[], Person::class];
    }

    #[DataProvider('typedArrayProvider')]
    public function testDecodeTypedArray(array $data, string $type): void
    {
        $container = $this->decoder->decode($data);
        $this->assertIsArray($container->decodeArray($type));
    }

    public static function typedArrayValueTypeMismatchProvider(): Generator
    {
        yield [[1, 0], 'bool'];
        yield [['a', 'b', 'c'], 'int'];
        yield [['1', '2', '3'], 'int'];
        yield [[1, 2, 3], 'string'];
    }

    #[DataProvider('typedArrayValueTypeMismatchProvider')]
    public function testDecodeTypedArrayValueTypeMismatchException(array $data, string $type): void
    {
        $container = $this->decoder->decode($data);
        $this->expectException(ValueTypeMismatchException::class);
        $container->decodeArray($type);
    }

    public function testDecodeTypedArrayInvalidValueException(): void
    {
        $container = $this->decoder->decode(['apple', 'coconut']);
        $this->expectException(InvalidValueException::class);
        $container->decodeArray(Fruit::class);
    }
}
