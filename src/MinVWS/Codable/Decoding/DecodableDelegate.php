<?php

namespace MinVWS\Codable\Decoding;

use MinVWS\Codable\Exceptions\CodableException;

/**
 * External decodable implementation.
 */
interface DecodableDelegate
{
    /**
     * Decode to the given class.
     *
     * @template T of object
     *
     * @param class-string<T>   $class     Target class.
     * @param DecodingContainer $container Decoding container.
     * @param T|null            $object    Decode into the given object.
     *
     * @return T
     *
     * @throws CodableException
     */
    public function decode(string $class, DecodingContainer $container, ?object $object = null): object;
}
