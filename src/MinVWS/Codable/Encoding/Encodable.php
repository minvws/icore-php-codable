<?php

namespace MinVWS\Codable\Encoding;

use MinVWS\Codable\Exceptions\CodableException;

interface Encodable
{
    /**
     * Encode this object.
     *
     * @param EncodingContainer $container
     *
     * @throws CodableException
     */
    public function encode(EncodingContainer $container): void;
}
