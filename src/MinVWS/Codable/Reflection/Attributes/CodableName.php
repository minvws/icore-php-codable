<?php

declare(strict_types=1);

namespace MinVWS\Codable\Reflection\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class CodableName
{
    public function __construct(public string $name)
    {
    }
}
