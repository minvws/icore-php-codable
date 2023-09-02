<?php

declare(strict_types=1);

namespace MinVWS\Codable\Reflection\Attributes;

use Attribute;
use MinVWS\Codable\Reflection\CodableIgnoreType;

#[Attribute]
class CodableIgnore
{
    public function __construct(public CodableIgnoreType $type = CodableIgnoreType::Always)
    {
    }
}
