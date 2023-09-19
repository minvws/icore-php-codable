<?php

namespace MinVWS\Codable\Decoding;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use MinVWS\Codable\Exceptions\CodableException;
use MinVWS\Codable\Exceptions\DateTimeFormatException;
use MinVWS\Codable\Exceptions\KeyNotFoundException;
use MinVWS\Codable\Exceptions\KeyTypeMismatchException;
use MinVWS\Codable\Exceptions\PathNotFoundException;
use MinVWS\Codable\Exceptions\ValueNotFoundException;
use MinVWS\Codable\Exceptions\ValueTypeMismatchException;

class DecodingContainer
{
    public function __construct(
        private readonly mixed $value,
        private readonly DecodingContext $context,
        private readonly ?DecodingContainer $parent = null,
        private readonly int|string|null $key = null
    ) {
    }

    public function getRawValue(): mixed
    {
        return $this->value;
    }

    /**
     * Is non-null data present?
     *
     * @return bool
     */
    public function isPresent(): bool
    {
        return $this->getRawValue() !== null;
    }

    /**
     * @throws ValueNotFoundException
     */
    public function validatePresent(): void
    {
        if (!$this->isPresent()) {
            throw new ValueNotFoundException($this->getPath());
        }
    }

    public function exists(): bool
    {
        if ($this->isPresent() || $this->getParent() === null || $this->getKey() === null) {
            return true;
        }

        return $this->getParent()->contains($this->getKey());
    }

    /**
     * @throws PathNotFoundException
     */
    public function validateExists(): void
    {
        if (!$this->exists()) {
            throw new PathNotFoundException($this->getPath());
        }
    }

    /**
     * @throws PathNotFoundException|ValueNotFoundException
     */
    public function validateExistsAndPresent(): void
    {
        $this->validateExists();
        $this->validatePresent();
    }

    public function getContext(): DecodingContext
    {
        return $this->context;
    }

    public function getRoot(): DecodingContainer
    {
        if ($this->getParent() !== null) {
            return $this->getParent()->getRoot();
        }

        return $this;
    }

    public function getParent(): ?DecodingContainer
    {
        return $this->parent;
    }

    public function getKey(): string|int|null
    {
        return $this->key;
    }

    /**
     * @param 'string'|'int'|null $type Expected key type.
     *
     * @return string|int
     *
     * @throws PathNotFoundException | KeyNotFoundException | KeyTypeMismatchException
     */
    public function decodeKey(?string $type = null): int|string
    {
        $this->validateExists();

        if ($this->getKey() === null) {
            throw new KeyNotFoundException($this->getPath());
        }

        $type = $type ?: gettype($this->getKey());

        return match ($type) {
            'string' => $this->decodeStringKey(),
            default => $this->decodeIntKey()
        };
    }

    /**
     * @param 'string'|'int'|null $type Expected key type.
     *
     * @throws KeyTypeMismatchException | KeyNotFoundException
     */
    public function decodeKeyIfExists(?string $type = null): int|string|null
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            return $this->decodeKey($type);
        } catch (PathNotFoundException) {
            return null;
        }
    }

    /**
     * @param 'string'|'int'|null $type Expected key type.
     *
     * @throws KeyTypeMismatchException
     */
    public function decodeKeyIfPresent(?string $type = null): int|string|null
    {
        if ($this->getKey() === null) {
            return null;
        }

        try {
            return $this->decodeKey($type);
        } catch (PathNotFoundException | KeyNotFoundException) {
            return null;
        }
    }

    /**
     * @throws PathNotFoundException | KeyNotFoundException | KeyTypeMismatchException
     */
    public function decodeStringKey(): string
    {
        $this->validateExists();

        if ($this->getKey() === null) {
            throw new KeyNotFoundException($this->getPath());
        } elseif (!is_string($this->getKey())) {
            throw new KeyTypeMismatchException($this->getPath(), gettype($this->getKey()), 'string');
        } else {
            return $this->getKey();
        }
    }


    /**
     * @throws KeyNotFoundException | KeyTypeMismatchException
     */
    public function decodeStringKeyIfExists(): ?string
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            return $this->decodeStringKey();
        } catch (PathNotFoundException) {
            return null;
        }
    }

    /**
     * @throws KeyTypeMismatchException
     */
    public function decodeStringKeyIfPresent(): ?string
    {
        if ($this->getKey() === null) {
            return null;
        }


        try {
            return $this->decodeStringKey();
        } catch (PathNotFoundException | KeyNotFoundException) {
            return null;
        }
    }

    /**
     * @throws PathNotFoundException | KeyNotFoundException | KeyTypeMismatchException
     */
    public function decodeIntKey(): int
    {
        $this->validateExists();

        if ($this->getKey() === null) {
            throw new KeyNotFoundException($this->getPath());
        } elseif (!is_int($this->getKey())) {
            throw new KeyTypeMismatchException($this->getPath(), gettype($this->getKey()), 'int');
        } else {
            return $this->getKey();
        }
    }

    /**
     * @throws KeyNotFoundException | KeyTypeMismatchException
     */
    public function decodeIntKeyIfExists(): ?int
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            return $this->decodeIntKey();
        } catch (PathNotFoundException) {
            return null;
        }
    }

    /**
     * @throws KeyTypeMismatchException
     */
    public function decodeIntKeyIfPresent(): ?int
    {
        if ($this->getKey() === null) {
            return null;
        }


        try {
            return $this->decodeIntKey();
        } catch (PathNotFoundException | KeyNotFoundException) {
            return null;
        }
    }

    public function getPath(): array
    {
        if ($this->getParent() !== null && $this->getKey() !== null) {
            return array_merge($this->getParent()->getPath(), [$this->getKey()]);
        } else {
            return [];
        }
    }

    /**
     * Decode to the given type or detected type.
     *
     * @param string|class-string|null $type        PHP type or full class name.
     * @param string|class-string|null $elementType In case of an array; element PHP type or full class name.
     * @param mixed|null               $current     Current value (only supported for objects).
     *
     * @return mixed
     *
     * @throws PathNotFoundException | ValueNotFoundException | ValueTypeMismatchException | CodableException
     */
    public function decode(?string $type = null, ?string $elementType = null, mixed $current = null): mixed
    {
        $this->validateExists();

        if ($type === 'mixed') {
            $type = null;
        }

        $type = $type ?? gettype($this->value);

        return match ($type) {
            'null', 'NULL' => $this->decodeNull(),
            'string' => $this->decodeString(),
            'int', 'integer', 'long' => $this->decodeInt(),
            'float', 'double' => $this->decodeFloat(),
            'bool', 'boolean' => $this->decodeBool(),
            'array' => $this->decodeArray($elementType),
            default => $this->decodeObject(class_exists($type) ? $type : null, is_object($current) ? $current : null)
        };
    }

    /**
     * Decode to the given type or detected type but returns null if the current path does not exist.
     *
     * @param string|class-string|null $type        PHP type or full class name.
     * @param string|class-string|null $elementType In case of an array; element PHP type or full class name.
     * @param mixed|null               $current     Current value (only supported for objects).
     *
     * @throws ValueNotFoundException | ValueTypeMismatchException | CodableException
     */
    public function decodeIfExists(?string $type = null, ?string $elementType = null, mixed $current = null): mixed
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            return $this->decode($type, $elementType, $current);
        } catch (PathNotFoundException) {
            return null;
        }
    }

    /**
     * Decode to the given type or detected type but returns null if the current path does not exist or is null.
     *
     * @param string|class-string|null $type        PHP type or full class name.
     * @param string|class-string|null $elementType In case of an array; element PHP type or full class name.
     * @param mixed|null               $current     Current value (only supported for objects).
     *
     * @throws ValueTypeMismatchException | CodableException
     */
    public function decodeIfPresent(?string $type = null, ?string $elementType = null, mixed $current = null): mixed
    {
        if (!$this->isPresent()) {
            return null;
        }

        try {
            return $this->decode($type, $elementType, $current);
        } catch (PathNotFoundException | ValueNotFoundException) {
            return null;
        }
    }

    /**
     * @throws PathNotFoundException | ValueNotFoundException | ValueTypeMismatchException
     */
    public function decodeString(): string
    {
        $this->validateExistsAndPresent();

        if (!is_string($this->getRawValue())) {
            throw new ValueTypeMismatchException($this->getPath(), gettype($this->getRawValue()), 'string');
        }

        return $this->getRawValue();
    }

    /**
     * @throws ValueNotFoundException | ValueTypeMismatchException
     */
    public function decodeStringIfExists(): ?string
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            return $this->decodeString();
        } catch (PathNotFoundException) {
            return null;
        }
    }

    /**
     * @throws ValueTypeMismatchException
     */
    public function decodeStringIfPresent(): ?string
    {
        if (!$this->isPresent()) {
            return null;
        }

        try {
            return $this->decodeString();
        } catch (PathNotFoundException | ValueNotFoundException) {
            return null;
        }
    }

    /**
     * @throws PathNotFoundException | ValueNotFoundException | ValueTypeMismatchException
     */
    public function decodeInt(): int
    {
        $this->validateExistsAndPresent();

        if (!is_int($this->getRawValue())) {
            throw new ValueTypeMismatchException($this->getPath(), gettype($this->getRawValue()), 'int');
        }

        return $this->getRawValue();
    }

    /**
     * @throws ValueNotFoundException | ValueTypeMismatchException
     */
    public function decodeIntIfExists(): ?int
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            return $this->decodeInt();
        } catch (PathNotFoundException) {
            return null;
        }
    }

    /**
     * @throws ValueTypeMismatchException
     */
    public function decodeIntIfPresent(): ?int
    {
        if (!$this->isPresent()) {
            return null;
        }

        try {
            return $this->decodeInt();
        } catch (PathNotFoundException | ValueNotFoundException) {
            return null;
        }
    }

    /**
     * @throws PathNotFoundException | ValueNotFoundException | ValueTypeMismatchException
     */
    public function decodeFloat(): float
    {
        $this->validateExistsAndPresent();

        if (!is_float($this->getRawValue())) {
            throw new ValueTypeMismatchException($this->getPath(), gettype($this->getRawValue()), 'float');
        }

        return $this->getRawValue();
    }

    /**
     * @throws ValueNotFoundException | ValueTypeMismatchException
     */
    public function decodeFloatIfExists(): ?float
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            return $this->decodeFloat();
        } catch (PathNotFoundException) {
            return null;
        }
    }

    /**
     * @throws ValueTypeMismatchException
     */
    public function decodeFloatIfPresent(): ?float
    {
        if (!$this->isPresent()) {
            return null;
        }

        try {
            return $this->decodeFloat();
        } catch (PathNotFoundException | ValueNotFoundException) {
            return null;
        }
    }

    /**
     * @throws PathNotFoundException | ValueNotFoundException | ValueTypeMismatchException
     */
    public function decodeBool(): bool
    {
        $this->validateExistsAndPresent();

        if (!is_bool($this->getRawValue())) {
            throw new ValueTypeMismatchException($this->getPath(), gettype($this->getRawValue()), 'bool');
        }

        return $this->getRawValue();
    }

    /**
     * @throws ValueNotFoundException | ValueTypeMismatchException
     */
    public function decodeBoolIfExists(): ?bool
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            return $this->decodeBool();
        } catch (PathNotFoundException) {
            return null;
        }
    }

    /**
     * @throws ValueTypeMismatchException
     */
    public function decodeBoolIfPresent(): ?bool
    {
        if (!$this->isPresent()) {
            return null;
        }

        try {
            return $this->decodeBool();
        } catch (PathNotFoundException | ValueNotFoundException) {
            return null;
        }
    }

    /**
     * @throws PathNotFoundException | ValueTypeMismatchException
     */
    public function decodeNull(): null
    {
        $this->validateExists();

        if ($this->getRawValue() !== null) {
            throw new ValueTypeMismatchException($this->getPath(), gettype($this->getRawValue()), 'null');
        }

        return null;
    }

    /**
     * @throws ValueTypeMismatchException
     */
    public function decodeNullIfExists(): null
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            return $this->decodeNull();
        } catch (PathNotFoundException) {
            return null;
        }
    }

    /**
     * @template T of DateTime|DateTimeImmutable
     *
     * @param class-string<T> $class
     *
     * @return T
     *
     * @throws PathNotFoundException | ValueNotFoundException | ValueTypeMismatchException | DateTimeFormatException
     */
    public function decodeDateTime(string $format = null, DateTimeZone $tz = null, string $class = DateTimeImmutable::class): DateTime|DateTimeImmutable
    {
        $string = $this->decodeString();

        if ($format === null) {
            try {
                $dateTime = $class::createFromInterface(new DateTimeImmutable($string, $tz));
            } catch (Exception) {
                throw new DateTimeFormatException($this->getPath(), '<any>');
            }
        } else {
            $dateTime = $class::createFromFormat('!' . $format, $string, $tz);
            if ($dateTime === false) {
                throw new DateTimeFormatException($this->getPath(), $format);
            }
        }

        assert(is_a($dateTime, $class));
        return $dateTime;
    }

    /**
     * @template T of DateTime|DateTimeImmutable
     *
     * @param class-string<T> $class
     *
     * @throws ValueNotFoundException | ValueTypeMismatchException | DateTimeFormatException
     */
    public function decodeDateTimeIfExists(string $format = null, DateTimeZone $tz = null, string $class = DateTimeImmutable::class): DateTime|DateTimeImmutable|null
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            return $this->decodeDateTime($format, $tz, $class);
        } catch (PathNotFoundException) {
            return null;
        }
    }

    /**
     * @template T of DateTime|DateTimeImmutable
     *
     * @param class-string<T> $class
     *
     * @throws ValueTypeMismatchException | DateTimeFormatException
     */
    public function decodeDateTimeIfPresent(string $format = null, DateTimeZone $tz = null, string $class = DateTimeImmutable::class): DateTime|DateTimeImmutable|null
    {
        if (!$this->isPresent()) {
            return null;
        }

        try {
            return $this->decodeDateTime($format, $tz, $class);
        } catch (PathNotFoundException | ValueNotFoundException) {
            return null;
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T>|null $class  Object class.
     * @param T|null               $object Decode into the given object.
     * @param bool                 $strict Check if the value in the container is an object
     *
     * @return ($class is null ? object : T)
     *
     * @throws PathNotFoundException | ValueTypeMismatchException | ValueNotFoundException | CodableException
     */
    public function decodeObject(?string $class = null, ?object $object = null, bool $strict = false): object
    {
        $this->validateExistsAndPresent();

        if ($strict && !is_object($this->getRawValue())) {
            throw new ValueTypeMismatchException($this->getPath(), gettype($this->getRawValue()), $class ?? 'object');
        }

        $delegate = $class !== null ? $this->getContext()->getDelegate($class) : null;

        if ($delegate instanceof DecodableDelegate) {
            assert($class !== null);
            return $delegate->decode($class, $this, $object);
        } elseif (is_callable($delegate)) {
            assert($class !== null);
            $result = call_user_func($delegate, $this, $object);
            assert(is_object($result) && is_a($result, $class));
            return $result;
        } elseif ($delegate !== null && is_a($delegate, StaticDecodableDelegate::class, true)) {
            assert($class !== null);
            return $delegate::decode($class, $this, $object);
        } elseif (is_string($class) && is_a($class, Decodable::class, true)) {
            return $class::decode($this, is_object($object) && is_a($object, $class) ? $object : null);
        } elseif (!is_object($this->getRawValue())) {
            // we do this check as one of the last things because certain classes like DateTime
            // are encoded to string literals
            throw new ValueTypeMismatchException($this->getPath(), gettype($this->getRawValue()), $class ?? 'object');
        } elseif ($object === null) {
            return $this->getRawValue();
        } else {
            foreach (get_object_vars($this->getRawValue()) as $k => $v) {
                $object->$k = $v;
            }

            return $object;
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T>|null $class  Object class.
     * @param T|null               $object Decode into the given object.
     * @param bool                 $strict Check if the value in the container is an object
     *
     * @return ($class is null ? object|null : T|null)
     *
     * @throws ValueTypeMismatchException | ValueNotFoundException| CodableException
     */
    public function decodeObjectIfExists(?string $class = null, ?object $object = null, bool $strict = false)
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            return $this->decodeObject($class, $object, $strict);
        } catch (PathNotFoundException) {
            return null;
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T>|null $class  Object class.
     * @param T|null               $object Decode into the given object.
     * @param bool                 $strict Check if the value in the container is an object
     *
     * @return ($class is null ? object|null : T|null)
     *
     * @throws ValueTypeMismatchException | CodableException
     */
    public function decodeObjectIfPresent(?string $class = null, ?object $object = null, bool $strict = false): ?object
    {
        if (!$this->isPresent()) {
            return null;
        }

        try {
            return $this->decodeObject($class, $object, $strict);
        } catch (PathNotFoundException | ValueNotFoundException) {
            return null;
        }
    }

    /**
     * @template T of object
     *
     * @param callable|string|class-string<T>|null $iteratorOrElementType
     * @param 'string'|'int'|null $keyType
     *
     * @return ($iteratorOrElementType is class-string<T> ? array<T> : array)
     *
     * @throws PathNotFoundException | ValueTypeMismatchException | ValueNotFoundException | CodableException
     */
    public function decodeArray(callable|string|null $iteratorOrElementType = null, ?string $keyType = null, bool $strict = false): array
    {
        $this->validateExistsAndPresent();

        $items = $this->getRawValue();
        if (!$strict && is_object($items)) {
            $items = get_object_vars($items);
        }

        if (!is_array($items)) {
            throw new ValueTypeMismatchException($this->getPath(), gettype($items), 'array');
        }

        $iterator = $iteratorOrElementType;
        if (!is_callable($iterator)) {
            $elementType = $iteratorOrElementType;
            $iterator = fn ($c) => $c->decode($elementType);
        }

        return array_combine(
            array_keys($items),
            array_map(
                function ($k, $v) use ($iterator, $keyType) {
                    $itemContainer = new self($v, $this->getContext(), $this, $k);
                    $itemContainer->decodeKey($keyType); // just to check the key type
                    return $iterator($itemContainer);
                },
                array_keys($items),
                array_values($items)
            )
        );
    }

    /**
     * @template T of object
     *
     * @param callable|string|class-string<T>|null $iteratorOrElementType
     * @param 'string'|'int'|null $keyType
     *
     * @return ($iteratorOrElementType is class-string<T> ? array<T>|null : array|null)
     *
     * @throws ValueNotFoundException | ValueTypeMismatchException | CodableException
     */
    public function decodeArrayIfExists(callable|string|null $iteratorOrElementType = null, ?string $keyType = null, bool $strict = false): ?array
    {
        if (!$this->exists()) {
            return null;
        }

        try {
            return $this->decodeArray($iteratorOrElementType, $keyType, $strict);
        } catch (PathNotFoundException) {
            return null;
        }
    }

    /**
     * @template T of object
     *
     * @param callable|string|class-string<T>|null $iteratorOrElementType
     * @param 'string'|'int'|null $keyType
     *
     * @return ($iteratorOrElementType is class-string<T> ? array<T>|null : array|null)
     *
     * @throws ValueTypeMismatchException | CodableException
     */
    public function decodeArrayIfPresent(callable|string|null $iteratorOrElementType = null, ?string $keyType = null, bool $strict = false): ?array
    {
        if (!$this->isPresent()) {
            return null;
        }

        try {
            return $this->decodeArray($iteratorOrElementType, $keyType, $strict);
        } catch (PathNotFoundException | ValueNotFoundException) {
            return null;
        }
    }

    /**
     * @param 'int'|'string'|null $type Expected key type.
     *
     * @return ($type is 'int' ? array<int> : ($type is 'string' ? array<string> : array))
     *
     * @throws PathNotFoundException | ValueNotFoundException | ValueTypeMismatchException
     */
    public function decodeArrayKeys(?string $type = null): array
    {
        $this->validateExistsAndPresent();

        if (!is_array($this->getRawValue()) && !is_object($this->getRawValue())) {
            throw new ValueTypeMismatchException($this->getPath(), gettype($this->getRawValue()), 'array');
        }

        $items = $this->getRawValue();
        if (is_object($items)) {
            $items = get_object_vars($items);
        }

        return array_keys($items);
    }

    /**
     * @param 'int'|'string'|null $type Expected key type.
     *
     * @return ($type is 'int' ? array<int>|null : ($type is 'string' ? array<string>|null : array|null))
     *
     * @throws ValueNotFoundException | ValueTypeMismatchException
     */
    public function decodeArrayKeysIfExists(?string $type = null): ?array
    {
        try {
            return $this->decodeArrayKeys($type);
        } catch (PathNotFoundException) {
            return null;
        }
    }

    /**
     * @param 'int'|'string'|null $type Expected key type.
     *
     * @return ($type is 'int' ? array<int>|null : ($type is 'string' ? array<string>|null : array|null))
     *
     * @throws ValueTypeMismatchException
     */
    public function decodeArrayKeysIfPresent(?string $type = null): ?array
    {
        try {
            return $this->decodeArrayKeys($type);
        } catch (PathNotFoundException | ValueNotFoundException) {
            return null;
        }
    }

    /**
     * Checks if the given nested container exists.
     */
    public function contains(int|string $key): bool
    {
        if (is_object($this->value) && is_string($key)) {
            return property_exists($this->value, $key);
        } elseif (is_array($this->value)) {
            return array_key_exists($key, $this->value);
        } else {
            return false;
        }
    }

    /**
     * Returns the nested container for the given key.
     */
    public function nestedContainer(string|int $key): DecodingContainer
    {
        if (is_object($this->value)) {
            $nestedValue = $this->value->$key ?? null;
        } elseif (is_array($this->value)) {
            $nestedValue = $this->value[$key] ?? null;
        } else {
            $nestedValue = null;
        }

        return new DecodingContainer($nestedValue, $this->getContext()->createChildContext(), $this, $key);
    }

    public function nestedContainerForPath(array $path): DecodingContainer
    {
        $container = $this;
        foreach ($path as $key) {
            $container = $container->nestedContainer($key);
        }

        return $container;
    }

    public function nestedContainerForPathIfExists(array $path): ?DecodingContainer
    {
        $container = $this;
        foreach ($path as $key) {
            if (!$container->contains($key)) {
                return null;
            }

            $container = $container->nestedContainer($key);
        }

        return $container;
    }

    public function __isset(string $key): bool
    {
        return $this->contains($key);
    }

    public function __get(string $key): DecodingContainer
    {
        return $this->nestedContainer($key);
    }
}
