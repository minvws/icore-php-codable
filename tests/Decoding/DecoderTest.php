<?php

declare(strict_types=1);

namespace MinVWS\Tests\Codable\Decoding;

use Generator;
use MinVWS\Codable\Decoding\Decoder;
use MinVWS\Codable\Decoding\DecodingContext;
use MinVWS\Tests\Codable\Shared\Fruit;
use MinVWS\Tests\Codable\Shared\FruitSalad;
use MinVWS\Tests\Codable\Shared\Person;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DecoderTest extends TestCase
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

    #[DataProvider('decodeProvider')]
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

    public static function decodingModeProvider(): Generator
    {
        yield [null, true];
        yield [DecodingContext::MODE_LOAD, true];
        yield [DecodingContext::MODE_INPUT, false];
    }

    #[DataProvider('decodingModeProvider')]
    public function testDecodingMode(?string $mode, bool $expectsAuthor): void
    {
        $data = [
            'title' => 'Banana Orange Salad',
            'fruits' => ['banana', 'orange'],
            'description' => 'Wonderful salad of banana mixed with oranges',
            'author' => 'John Doe'
        ];

        $decoder = new Decoder();
        $decoder->getContext()->setMode($mode);
        $salad = $decoder->decode($data)->decodeObject(FruitSalad::class);
        $this->assertEquals($data['title'], $salad->title);
        $this->assertEquals($data['description'], $salad->description);
        $this->assertCount(count($data['fruits']), $salad->fruits);
        $this->assertEquals($expectsAuthor ? $data['author'] : null, $salad->author);
    }
}
