<?php

declare(strict_types=1);

namespace MinVWS\Codable\Reflection\Attributes;

use Attribute;

#[Attribute]
readonly class CodableName
{
    public function __construct(public string $name)
    {
    }
}
