<?php

namespace MinVWS\Tests\Codable\Shared;

use MinVWS\Codable\Coding\Codable;
use MinVWS\Codable\Coding\CodableSupport;
use MinVWS\Codable\Decoding\DecodingContext;
use MinVWS\Codable\Encoding\EncodingContext;
use MinVWS\Codable\Reflection\Attributes\CodableArray;
use MinVWS\Codable\Reflection\Attributes\CodableModes;

class FruitSalad implements Codable
{
    use CodableSupport;

    public function __construct(
        public string $title,
        public string $description,
        #[CodableArray(elementType: Fruit::class)]
        public readonly array $fruits,
        #[CodableModes(
            encodingModes: [EncodingContext::MODE_STORE],
            decodingModes: [DecodingContext::MODE_LOAD],
        )]
        public ?string $author = null
    ) {
    }
}
