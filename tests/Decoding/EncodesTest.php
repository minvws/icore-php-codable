<?php

namespace MinVWS\Tests\Codable\Decoding;

use Generator;
use MinVWS\Codable\Encoding\Encoder;
use MinVWS\Tests\Codable\Shared\Collection;
use MinVWS\Tests\Codable\Shared\Fruit;
use MinVWS\Tests\Codable\Shared\Person;
use MinVWS\Tests\Codable\Shared\Vegetable;
use MinVWS\Tests\Codable\Traits\WithFaker;
use PHPUnit\Framework\TestCase;

class EncodesTest extends TestCase
{
    use WithFaker;

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

    public static function encodeProvider(): Generator
    {
        yield [self::buildPerson(true, true, true, 1, 1, 0)];
        yield [self::buildPerson(true, true, true, 3, 2, 2)];
        yield [self::buildPerson(false, false, false, 0, 1, 1)];
        yield [self::buildPerson(false, true, false, 2, 0, 0)];
    }

    /**
     * @dataProvider encodeProvider
     */
    public function testEncode(Person $person): void
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
