<?php

namespace MinVWS\Codable\Decoding;

use MinVWS\Codable\Exceptions\CodableException;

interface Decodable
{
    /**
     * Decode to this class.
     *
     * @param DecodingContainer $container Decoding container.
     * @param static|null       $object    Decode into the given object.
     *
     * @return static
     *
     * @throws CodableException
     */
    public static function decode(DecodingContainer $container, ?self $object = null): static;
}
