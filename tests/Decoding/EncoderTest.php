<?php

declare(strict_types=1);

namespace MinVWS\Tests\Codable\Decoding;

use DateTimeImmutable;
use Generator;
use MinVWS\Codable\Encoding\Encoder;
use MinVWS\Codable\Encoding\EncodingContext;
use MinVWS\Tests\Codable\Shared\Fruit;
use MinVWS\Tests\Codable\Shared\FruitBasket;
use MinVWS\Tests\Codable\Shared\Person;
use MinVWS\Tests\Codable\Shared\Vegetable;
use MinVWS\Tests\Codable\Traits\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EncoderTest extends TestCase
{
    use WithFaker;

    public static function encodeSimpleTypeProvider(): Generator
    {
        yield 'null' => [null, null];
        yield 'true' => [true, true];
        yield 'false' => [false, false];
        yield '0' => [0, 0];
        yield '42' => [42, 42];
        yield '12.3' => [12.3, 12.3];
        yield '1.0' => [1.0, 1.0];
        yield 'Fruit::Apple' => [Fruit::Apple, 'apple'];
        yield 'Vegetable::Tomato' => [Vegetable::Tomato, 'Tomato'];
        yield "['a', 'b', 'c']" => [['a', 'b', 'c'], ['a', 'b', 'c']];
        yield "[4, 5, 6]" => [[4, 5, 6], [4, 5, 6]];
        yield 'Mixed array' => [
            [1, 'b', 'a' => Fruit::Banana, null, 42 => Vegetable::Lettuce],
            [1, 'b', 'a' => 'banana', null, 42 => 'Lettuce']
        ];
        yield 'Fruit basket' => [
            new FruitBasket([Fruit::Orange, Fruit::Apple, Fruit::Orange]),
            (object)['fruits' => ['orange', 'apple', 'orange']]
        ];

        $now = new DateTimeImmutable();
        yield 'DateTime' => [$now, $now->format('Y-m-d\TH:i:sp')];

        $dtContext = new EncodingContext();
        $dtContext->setDateTimeFormat('Y-m-d');
        yield 'DateTime format' => [$now, $now->format('Y-m-d'), $dtContext];
    }

    #[DataProvider('encodeSimpleTypeProvider')]
    public function testEncodeSimpleType(mixed $input, mixed $expectedOutput, ?EncodingContext $context = null): void
    {
        $encoder = new Encoder($context);
        $output = $encoder->encode($input);
        $this->assertEquals($expectedOutput, $output);
    }

    private static function buildPerson(
        bool $hasInfix,
        bool $hasBirthDate,
        bool $hasFavoriteFruit,
        int $dislikedFruitCount,
        int $dislikedVegetableCount,
        int $notesCount
    ): Person {
        $person = new Person(firstName: self::faker()->firstName, infix: $hasInfix ? 'van' : null, lastName: self::faker()->lastName);
        $person->birthDate = $hasBirthDate ? self::faker()->dateTimeBetween('-80 years') : null;
        $person->country = self::faker()->country;
        $favoriteFruit = $hasFavoriteFruit ? self::faker()->randomElement(Fruit::cases()) : null;
        assert($favoriteFruit === null || $favoriteFruit instanceof Fruit);
        $person->favoriteFruit = $favoriteFruit;
        foreach (self::faker()->randomElements(Fruit::cases(), $dislikedFruitCount) as $fruit) {
            $person->addDislikedFruit($fruit);
        }
        foreach (self::faker()->randomElements(Vegetable::cases(), $dislikedVegetableCount) as $vegetable) {
            $person->addDislikedVegetable($vegetable);
        }
        for ($i = 0; $i < $notesCount; $i++) {
            $person->notes[] = self::faker()->realText;
        }
        return $person;
    }

    public static function encodeComplexTypeProvider(): Generator
    {
        yield [self::buildPerson(true, true, true, 1, 1, 0)];
        yield [self::buildPerson(true, true, true, 3, 2, 2)];
        yield [self::buildPerson(false, false, false, 0, 1, 1)];
        yield [self::buildPerson(false, true, false, 2, 0, 0)];
    }

    #[DataProvider('encodeComplexTypeProvider')]
    public function testEncodeComplexType(Person $person): void
    {
        $encoder = new Encoder();
        $encoder->getContext()->setUseAssociativeArraysForObjects(true);
        $data = $encoder->encode($person);
        $this->assertIsArray($data);
        $this->assertEquals($person->firstName, $data['firstName']);
        $this->assertEquals($person->infix, $data['infix']);
        $this->assertEquals($person->lastName, $data['surname']);
        $this->assertEquals($person->birthDate?->format('Y-m-d'), $data['birthDate']);
        $this->assertEquals($person->country, $data['address']['country']);
        $this->assertEquals($person->favoriteFruit?->value, $data['favoriteFruit']);
        $this->assertEquals(array_map(fn ($f) => $f->value, $person->getDislikedFruits()), $data['dislikedFruits']);
        $this->assertArrayNotHasKey('dislikedVegetables', $data);
        $this->assertEquals($person->notes->toArray(), $data['notes']);
    }
}
