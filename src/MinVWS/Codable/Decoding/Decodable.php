<?php

namespace MinVWS\Codable\Decoding;

use MinVWS\Codable\Exceptions\CodableException;

interface Decodable
{
    /**
     * Decode to this class.
     *
     * @param DecodingContainer $container Decoding container.
     * @param self|null         $object    Decode into the given object.
     *
     * @throws CodableException
     *
     * @SuppressWarnings("unused")
     */
    public static function decode(DecodingContainer $container, ?self $object = null): self;
}
