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

    protected static function newInstanceForCodableClass(ReflectionCodableClass $class, array &$values): self
    {
        $args = [];

        foreach ($values as $propertyName => $value) {
            $property = $class->getProperty($propertyName);
            if (!$property->isPromoted()) {
                continue;
            }

            $args[$propertyName] = $value;
            unset($values[$propertyName]);
        }

        $instance = $class->newInstanceArgs($args);
        assert($instance instanceof static);
        return $instance;
    }

    protected static function shouldDecodeProperty(ReflectionCodableProperty $property, DecodingContainer $container, ?object $object): bool
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
    protected static function decodeCodableProperty(
        ReflectionCodableProperty $property,
        DecodingContainer $container,
        ?object $object,
        array &$values
    ): void {
        if (!static::shouldDecodeProperty($property, $container, $object)) {
            return;
        }

        $callback = $property->getAttribute(CodableCallbacks::class)?->decode;
        if (isset($callback) && is_callable($callback)) {
            $values[$property->getName()] = call_user_func($callback, $container, $object);
            return;
        }

        $name = $property->getAttribute(CodableName::class)?->name ?? $property->getName();
        $propertyContainer = $container->nestedContainer($name);

        if ($propertyContainer->exists() && !$property->allowsNull()) {
            $propertyContainer->validatePresent();
        }

        if ($object === null && !$propertyContainer->exists() && $property->hasDefaultValue()) {
            $values[$property->getName()] = $property->getDefaultValue();
            return;
        }

        if ($object === null && !$propertyContainer->exists() && $property->allowsNull()) {
            $values[$property->getName()] = null;
            return;
        }

        if (!$propertyContainer->exists()) {
            return;
        }

        $type = $property->getType(ReflectionNamedType::class);

        if ($type->isBuiltin() && $type->getName() === 'array') {
            $attr = $property->getAttribute(CodableArray::class);
            $values[$property->getName()] = $propertyContainer->decodeArray($attr?->elementType, $attr?->keyType);
        } elseif (!$type->isBuiltin() && $property->hasAttribute(CodableArrayObject::class)) {
            $attr = $property->getAttribute(CodableArrayObject::class);
            assert($attr !== null);
            $arr = $propertyContainer->decodeArray($attr->elementType, $attr->keyType);
            $values[$property->getName()] = call_user_func($attr->factory, $arr);
        } elseif (!$type->isBuiltin() && is_a($type->getName(), DateTimeInterface::class, true)) {
            $attr = $property->getAttribute(CodableDateTime::class);
            $values[$property->getName()] = $propertyContainer->decodeDateTime($attr?->format, $attr?->timeZone);
        } else {
            $values[$property->getName()] = $propertyContainer->decode($type->getName());
        }
    }

    public static function decode(DecodingContainer $container, ?object $object = null): self
    {
        $class = ReflectionCodableClass::forClass(static::class);

        $values = [];
        foreach ($class->getProperties() as $property) {
            static::decodeCodableProperty($property, $container, $object, $values);
        }

        if ($object === null) {
            $object = static::newInstanceForCodableClass($class, $values);
        }

        foreach ($values as $propertyName => $value) {
            $property = $class->getProperty($propertyName);
            static::setValueForCodableProperty($property, $object, $value);
        }

        assert($object instanceof static);

        return $object;
    }
}
