<?php

namespace MinVWS\Codable\Decoding;

use MinVWS\Codable\Coding\Context;

/**
 * Decoding context.
 *
 * Can be used to register decodable delegates and
 * contextual values for decoding.
 */
class DecodingContext extends Context
{
    public const MODE_INPUT = 'input';
    public const MODE_LOAD  = 'load';

    public function createChildContext(): self
    {
        return new self($this);
    }
}
