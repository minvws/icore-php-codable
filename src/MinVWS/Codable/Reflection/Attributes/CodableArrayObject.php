<?php

declare(strict_types=1);

namespace MinVWS\Codable\Reflection\Attributes;

use Attribute;
use Closure;

#[Attribute]
readonly class CodableArrayObject extends CodableArray
{
    public Closure $factory;

    /**
     * @param callable $factory
     * @param class-string|'string'|'int'|'float'|'bool'|null $elementType
     * @param 'string'|'int'|null $keyType
     */
    public function __construct(
        callable $factory,
        ?string $elementType = null,
        ?string $keyType = null,
    ) {
        parent::__construct($elementType, $keyType);
        $this->factory = $factory(...);
    }
}
