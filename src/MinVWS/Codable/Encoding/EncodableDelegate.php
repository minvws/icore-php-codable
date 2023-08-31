<?php

namespace MinVWS\Codable\Encoding;

use MinVWS\Codable\Exceptions\CodableException;

/**
 * External encodable implementation for class.
 */
interface EncodableDelegate
{
    /**
     * Encode the given value.
     *
     * @throws CodableException
     */
    public function encode(object $value, EncodingContainer $container): void;
}
