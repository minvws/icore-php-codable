<?php

namespace MinVWS\Codable\Encoding;

use MinVWS\Codable\Exceptions\CodableException;

/**
 * External encodable implementation for class.
 */
interface StaticEncodableDelegate
{
    /**
     * Encode to the given class.
     *
     * @param object            $object
     * @param EncodingContainer $container
     *
     * @throws CodableException
     */
    public static function encode(object $object, EncodingContainer $container): void;
}
