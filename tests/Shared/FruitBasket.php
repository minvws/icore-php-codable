<?php

namespace MinVWS\Tests\Codable\Shared;

use MinVWS\Codable\Coding\Codable;
use MinVWS\Codable\Coding\CodableSupport;
use MinVWS\Codable\Reflection\Attributes\CodableArray;

class FruitBasket implements Codable
{
    use CodableSupport;

    public function __construct(
        #[CodableArray(elementType: Fruit::class)] public readonly array $fruits
    ) {
    }
}
