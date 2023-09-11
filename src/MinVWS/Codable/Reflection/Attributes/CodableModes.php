<?php

declare(strict_types=1);

namespace MinVWS\Codable\Reflection\Attributes;

use Attribute;
use MinVWS\Codable\Reflection\CodableIgnoreType;

#[Attribute]
readonly class CodableModes
{
    /**
     * @param array<string>|null $encodingModes
     * @param array<string>|null $decodingModes
     */
    public function __construct(
        public ?array $encodingModes = null,
        public ?array $decodingModes = null
    ) {
    }
}
