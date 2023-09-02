<?php

namespace MinVWS\Codable\Reflection;

use MinVWS\Codable\Exceptions\CodableException;
use ReflectionProperty;
use ReflectionType;

class ReflectionCodableProperty
{
    /**
     * @var array<string, object|null>
     */
    private array $attributes = [];

    public function __construct(
        private readonly ReflectionCodableClass $refCodableClass,
        private readonly ReflectionProperty $refProperty
    ) {
    }

    public function getReflectionProperty(): ReflectionProperty
    {
        return $this->refProperty;
    }

    public function getReflectionCodableClass(): ReflectionCodableClass
    {
        return $this->refCodableClass;
    }

    public function getName(): string
    {
        return $this->refProperty->getName();
    }

    /**
     * @param class-string<T> $expectedClass
     *
     * @return T
     *
     * @template T of ReflectionType
     */
    public function getType(string $expectedClass = ReflectionType::class): ReflectionType
    {
        $type = $this->getReflectionProperty()->getType();
        if ($type !== null && is_a($type, $expectedClass)) {
            return $type;
        }

        throw new CodableException(
            sprintf(
                "Property %s is of %s, expected type %s!",
                $this->getName(),
                $type === null ? 'unknown type' : 'type ' . get_class($type),
                $expectedClass
            )
        );
    }

    public function allowsNull(): bool
    {
        return $this->refProperty->getType()?->allowsNull() ?? true;
    }

    public function isReadOnly(): bool
    {
        return $this->refProperty->isReadOnly();
    }

    public function isPromoted(): bool
    {
        return $this->refProperty->isPromoted();
    }

    public function hasDefaultValue(): bool
    {
        return $this->refProperty->hasDefaultValue();
    }

    public function getDefaultValue(): mixed
    {
        return $this->refProperty->getDefaultValue();
    }

    /**
     * @param class-string $class
     */
    public function hasAttribute(string $class): bool
    {
        return $this->getAttribute($class) !== null;
    }

    /**
     * @param class-string<T> $class
     *
     * @return T|null
     *
     * @template T of object
     */
    public function getAttribute(string $class): ?object
    {
        if (array_key_exists($class, $this->attributes)) {
            $attr = $this->attributes[$class];
            assert($attr === null || is_a($attr, $class));
            return $attr;
        }

        $attrs = $this->refProperty->getAttributes($class);
        if (empty($attrs)) {
            $this->attributes[$class] = null;
            return null;
        }

        $attr = $attrs[0]->newInstance();
        assert(is_a($attr, $class));
        $this->attributes[$class] = $attr;
        return $attr;
    }
}
