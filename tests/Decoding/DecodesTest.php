<?php

namespace MinVWS\Tests\Codable\Decoding;

use Generator;
use MinVWS\Codable\Decoding\Decoder;
use MinVWS\Tests\Codable\Shared\Fruit;
use MinVWS\Tests\Codable\Shared\Person;
use PHPUnit\Framework\TestCase;

class DecodesTest extends TestCase
{
    public static function decodeProvider(): Generator
    {
        yield 'john' => [[
            'firstName' => 'John',
            'surname' => 'Doe',
            'birthDate' => '1994-04-01',
            'address' => ['country' => 'The Netherlands'],
            'favoriteFruit' => 'banana',
            'dislikedFruits' => ['apple'],
            'dislikedVegetables' => ['Lettuce', 'Spinach'],
            'notes' => ['Married to Jane']
        ]];

        yield 'jane' => [[
            'firstName' => 'Jane',
            'infix' => 'von',
            'surname' => 'Müllhousen',
            'birthDate' => null,
            'dislikedFruits' => ['apple', 'banana'],
            'dislikedVegetables' => []
        ]];
    }

    /**
     * @dataProvider decodeProvider
     */
    public function testDecode(array $data): void
    {
        $decoder = new Decoder();
        $person = $decoder->decode($data)->decodeObject(Person::class);
        $this->assertEquals($data['firstName'], $person->firstName);
        $this->assertEquals($data['infix'] ?? null, $person->infix);
        $this->assertEquals($data['surname'], $person->lastName);
        $this->assertEquals($data['birthDate'], $person->birthDate?->format('Y-m-d'));
        $this->assertEquals($data['address']['country'] ?? null, $person->country);
        $this->assertEquals($data['favoriteFruit'] ?? null, $person->favoriteFruit?->value);
        $this->assertEquals($data['dislikedFruits'] ?? [], array_map(fn (Fruit $f) => $f->value, $person->getDislikedFruits()));
        $this->assertEmpty($person->getDislikedVegetables());
        $this->assertEquals($data['notes'] ?? [], $person->notes->toArray());
    }
}
