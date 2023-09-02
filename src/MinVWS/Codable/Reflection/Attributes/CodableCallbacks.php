<?php

declare(strict_types=1);

namespace MinVWS\Codable\Reflection\Attributes;

use Attribute;
use Closure;
use MinVWS\Codable\Decoding\DecodingContainer;
use MinVWS\Codable\Encoding\EncodingContainer;

#[Attribute]
readonly class CodableCallbacks
{
    public Closure|array|null $encode;
    public Closure|array|null $decode;

    /**
     * @param (callable(EncodingContainer): void)|array{class-string, string}|null $encode
     * @param (callable(DecodingContainer, ?object): mixed)|array{class-string, string}|null $decode
     */
    public function __construct(
        callable|array|null $encode = null,
        callable|array|null $decode = null
    ) {
        $this->encode = is_callable($encode) ? $encode(...) : $encode;
        $this->decode = is_callable($decode) ? $decode(...) : $decode;
    }
}
