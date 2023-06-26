<?php

namespace MinVWS\Codable\Coding;

use DateTimeInterface;
use MinVWS\Codable\Decoding\DecodingContainer;
use MinVWS\Codable\Encoding\EncodingContainer;
use MinVWS\Codable\Exceptions\CodableException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;

/**
 * Coding keys trait that can be used to quickly add encodable/decodable support to a class.
 */
trait CodingKeys
{
    private static ?array $cachedCodingKeys = null;

    /**
     * Determine coding keys.
     *
     * Default implementation returns the key names defined in static::$codingKeys
     * (if any) or null (which means all public object properties).
     *
     * @return array<string|CodingKey>|null
     */
    protected static function codingKeys(): ?array
    {
        return static::$codingKeys ?? null;
    }

    /**
     * Modify the given coding keys
     */
    protected static function modifyCodingKey(CodingKey $key): void
    {
    }

    /**
     * Enrich coding keys with all necessary data.
     *
     * @throws CodableException
     */
    private static function fullCodingKeys(): array
    {
        try {
            $keys = static::codingKeys();

            // don't use reflection if it isn't truly necessary
            if ($keys !== null && count(array_filter($keys, fn($v) => $v instanceof CodingKey)) === count($keys)) {
                return $keys;
            }

            $refClass = new ReflectionClass(static::class);

            if ($keys === null) {
                $keys = array_map(fn(ReflectionProperty $p) => $p->getName(), $refClass->getProperties(ReflectionProperty::IS_PUBLIC));
            }

            $fullKeys = [];
            foreach ($keys as $key) {
                if (!($key instanceof CodingKey)) {
                    $key = new CodingKey($key);
                }

                if ($key->getType() === null) {
                    $refProperty = $refClass->getProperty($key->getKey());
                    $refType = $refProperty->getType();
                    $key->optional($refType->allowsNull());
                    if ($refType instanceof ReflectionNamedType) {
                        /** @noinspection PhpExpressionResultUnusedInspection */
                        $key->type($refType->getName());
                    }
                }

                static::modifyCodingKey($key);

                $fullKeys[] = $key;
            }

            return $fullKeys;
        } catch (ReflectionException $e) {
            throw new CodableException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns a cached version of the full coding keys.
     *
     * @throws CodableException
     */
    private static function cachedCodingKeys(): array
    {
        if (static::$cachedCodingKeys === null) {
            static::$cachedCodingKeys = static::fullCodingKeys();
        }

        return static::$cachedCodingKeys;
    }

    /**
     * Encodes the given key.
     *
     * @throws CodableException
     */
    protected function encodeCodingKey(EncodingContainer $container, CodingKey $key)
    {
        $name = $key->getName();
        $property = $key->getKey();

        if (is_a($key->getType(), DateTimeInterface::class, true)) {
            $container->$name->encodeDateTime($this->$property, $key->getDateTimeFormat(), $key->getDateTimeZone());
        } else {
            $container->$name = $this->$property;
        }
    }

    /**
     * Encode.
     *
     * @throws CodableException
     */
    public function encode(EncodingContainer $container): void
    {
        foreach (static::cachedCodingKeys() as $key) {
            $this->encodeCodingKey($container, $key);
        }
    }


    /**
     * Decodes the given key.
     *
     * @param static $object Target object.
     *
     * @throws CodableException
     */
    protected static function decodeCodingKey(DecodingContainer $container, CodingKey $key, self $object)
    {
        $name = $key->getName();
        $property = $key->getKey();

        if (!$container->contains($name) || $key->isReadOnly()) {
            return;
        }

        if (is_a($key->getType(), DateTimeInterface::class, true)) {
            if ($key->isOptional()) {
                $object->$property = $container->$name->decodeDateTimeIfPresent($key->getDateTimeFormat(), $key->getDateTimeZone(), $key->getType());
            } else {
                $object->$property = $container->$name->decodeDateTime($key->getDateTimeFormat(), $key->getDateTimeZone(), $key->getType());
            }
        } else {
            if ($key->isOptional()) {
                $object->$property = $container->$name->decodeIfPresent($key->getType(), $key->getElementType(), $object->$property ?? null);
            } else {
                $object->$property = $container->$name->decode($key->getType(), $key->getElementType(), $object->$property ?? null);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function decode(DecodingContainer $container, ?object $object = null): static
    {
        $object = $object ?? new static();

        foreach (static::cachedCodingKeys() as $key) {
            static::decodeCodingKey($container, $key, $object);
        }

        return $object;
    }
}
