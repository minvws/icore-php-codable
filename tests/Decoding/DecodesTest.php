<?php

namespace MinVWS\Tests\Codable\Decoding;

use MinVWS\Codable\Decoding\Decoder;
use MinVWS\Tests\Codable\Shared\Fruit;
use MinVWS\Tests\Codable\Shared\Person;
use PHPUnit\Framework\TestCase;

class DecodesTest extends TestCase
{
    public function testDecode(): void
    {
        $data = [
            'firstName' => 'John',
            'surname' => 'Doe',
            'birthDate' => '1994-04-01',
            'address' => ['country' => 'The Netherlands'],
            'favoriteFruit' => 'banana',
            'dislikedFruits' => ['apple'],
            'dislikedVegetables' => ['Lettuce', 'Spinach'],
            'notes' => ['Married to Jane']
        ];

        $decoder = new Decoder();
        $person = $decoder->decode($data)->decodeObject(Person::class);
        $this->assertEquals('John', $person->firstName);
        $this->assertEquals('Doe', $person->lastName);
        $this->assertEquals('1994-04-01', $person->birthDate?->format('Y-m-d'));
        $this->assertEquals($data['address']['country'], $person->country);
        $this->assertEquals(Fruit::Banana, $person->favoriteFruit);
        $this->assertEquals([Fruit::Apple], $person->getDislikedFruits());
        $this->assertEquals([], $person->getDislikedVegetables());
        $this->assertEquals($data['notes'][0], $person->notes[0]);
    }
}
