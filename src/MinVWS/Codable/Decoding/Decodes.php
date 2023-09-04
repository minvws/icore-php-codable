<?php

namespace MinVWS\Codable\Decoding;

use DateTimeInterface;
use MinVWS\Codable\Exceptions\CodableException;
use MinVWS\Codable\Reflection\Attributes\CodableArray;
use MinVWS\Codable\Reflection\Attributes\CodableArrayObject;
use MinVWS\Codable\Reflection\Attributes\CodableCallbacks;
use MinVWS\Codable\Reflection\Attributes\CodableDateTime;
use MinVWS\Codable\Reflection\Attributes\CodableIgnore;
use MinVWS\Codable\Reflection\Attributes\CodableModes;
use MinVWS\Codable\Reflection\Attributes\CodableName;
use MinVWS\Codable\Reflection\CodableIgnoreType;
use MinVWS\Codable\Reflection\ReflectionCodableClass;
use MinVWS\Codable\Reflection\ReflectionCodableProperty;
use ReflectionNamedType;

trait Decodes
{
    protected static function setValueForCodableProperty(ReflectionCodableProperty $property, object $object, mixed $value): void
    {
        $property->getReflectionProperty()->setValue($object, $value);
    }

    private static function extractPromotedPropertyValues(ReflectionCodableClass $class, array &$values): array
    {
        $promotedValues = [];

        foreach ($values as $propertyName => $value) {
            $property = $class->getProperty($propertyName);
            if ($property->isPromoted()) {
                $promotedValues[$propertyName] = $value;
                unset($values[$propertyName]);
            }
        }

        return $promotedValues;
    }

    protected static function newInstanceForCodableClass(ReflectionCodableClass $class, array $args): self
    {
        $instance = $class->newInstanceArgs($args);
        assert($instance instanceof static);
        return $instance;
    }

    protected static function shouldDecodeCodableProperty(ReflectionCodableProperty $property, DecodingContainer $container, ?object $object): bool
    {
        $ignore = $property->getAttribute(CodableIgnore::class)?->type;
        if ($ignore === CodableIgnoreType::Always || $ignore === CodableIgnoreType::OnDecode) {
            return false;
        }

        $decodingModes = $property->getAttribute(CodableModes::class)?->decodingModes;
        if ($decodingModes !== null && !in_array($container->getContext()->getMode(), $decodingModes)) {
            return false;
        }

        if ($object !== null && $property->isReadOnly()) {
            return false;
        }



        return true;
    }

    /**
     * @throws CodableException
     */
    private static function decodeNullCodableProperty(
        ReflectionCodableProperty $property,
        DecodingContainer $propertyContainer
    ): null {
        if ($property->allowsNull()) {
            return null;
        }

        // throws an exception
        $propertyContainer->validatePresent();
    }

    /**
     * @throws CodableException
     */
    private static function decodeArrayCodableProperty(ReflectionCodableProperty $property, DecodingContainer $propertyContainer): array
    {
        $attr = $property->getAttribute(CodableArray::class);
        return $propertyContainer->decodeArray($attr?->elementType, $attr?->keyType);
    }

    /**
     * @throws CodableException
     */
    private static function decodeArrayObjectCodableProperty(ReflectionCodableProperty $property, DecodingContainer $propertyContainer): object
    {
        $attr = $property->getAttribute(CodableArrayObject::class);
        assert($attr !== null);
        $arr = $propertyContainer->decodeArray($attr->elementType, $attr->keyType);
        $object = call_user_func($attr->factory, $arr);
        assert(is_object($object));
        return $object;
    }

    /**
     * @throws CodableException
     */
    private static function decodeDateTimeCodableProperty(ReflectionCodableProperty $property, DecodingContainer $propertyContainer): DateTimeInterface
    {
        $attr = $property->getAttribute(CodableDateTime::class);
        return $propertyContainer->decodeDateTime($attr?->format, $attr?->timeZone);
    }

    /**
     * @throws CodableException
     */
    private static function decodePresentCodableProperty(
        ReflectionCodableProperty $property,
        DecodingContainer $propertyContainer
    ): mixed {
        $type = $property->getType(ReflectionNamedType::class);

        if ($type->isBuiltin() && $type->getName() === 'array') {
            return self::decodeArrayCodableProperty($property, $propertyContainer);
        }

        if (!$type->isBuiltin() && $property->hasAttribute(CodableArrayObject::class)) {
            return self::decodeArrayObjectCodableProperty($property, $propertyContainer);
        }

        if (!$type->isBuiltin() && is_a($type->getName(), DateTimeInterface::class, true)) {
            return self::decodeDateTimeCodableProperty($property, $propertyContainer);
        }

        return $propertyContainer->decode($type->getName());
    }

    /**
     * @throws CodableException
     */
    protected static function decodeCodableProperty(
        ReflectionCodableProperty $property,
        DecodingContainer $container,
        ?object $object,
        array &$values
    ): void {
        if (!static::shouldDecodeCodableProperty($property, $container, $object)) {
            return;
        }

        $callback = $property->getAttribute(CodableCallbacks::class)?->decode;
        if (isset($callback) && is_callable($callback)) {
            $values[$property->getName()] = call_user_func($callback, $container, $object);
            return;
        }

        $name = $property->getAttribute(CodableName::class)?->name ?? $property->getName();
        $propertyContainer = $container->nestedContainer($name);
        if (!$propertyContainer->exists()) {
            return;
        }

        if (!$propertyContainer->isPresent()) {
            $values[$property->getName()] = static::decodeNullCodableProperty($property, $propertyContainer, $object);
            return;
        }

        $values[$property->getName()] = static::decodePresentCodableProperty($property, $propertyContainer);
    }

    public static function decode(DecodingContainer $container, ?object $object = null): self
    {
        $class = ReflectionCodableClass::forClass(static::class);

        $values = [];
        foreach ($class->getProperties() as $property) {
            static::decodeCodableProperty($property, $container, $object, $values);
        }

        if ($object === null) {
            $args = static::extractPromotedPropertyValues($class, $values);
            $object = static::newInstanceForCodableClass($class, $args);
        }

        foreach ($values as $propertyName => $value) {
            $property = $class->getProperty($propertyName);
            static::setValueForCodableProperty($property, $object, $value);
        }

        assert($object instanceof static);

        return $object;
    }
}
