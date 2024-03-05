<?php

declare(strict_types=1);

namespace MinVWS\Codable\Reflection\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class CodableArray
{
    /**
     * @param class-string|'string'|'int'|'float'|'bool'|null $elementType
     * @param 'string'|'int'|null $keyType
     */
    public function __construct(
        public ?string $elementType = null,
        public ?string $keyType = null
    ) {
    }
}
