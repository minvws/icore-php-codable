<?php

namespace MinVWS\Codable\Encoding;

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use JsonException;
use MinVWS\Codable\Exceptions\CodableException;
use MinVWS\Codable\Exceptions\CodablePathException;
use MinVWS\Codable\Exceptions\DateTimeFormatException;
use MinVWS\Codable\Exceptions\ValueTypeMismatchException;
use stdClass;
use UnitEnum;

class EncodingContainer
{
    public function __construct(
        private mixed &$value,
        private readonly EncodingContext $context,
        private readonly ?EncodingContainer $parent = null,
        private readonly string|int|null $key = null
    ) {
    }

    public function getContext(): EncodingContext
    {
        return $this->context;
    }

    public function getRoot(): self
    {
        if ($this->getParent() !== null) {
            return $this->getParent()->getRoot();
        }

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function getKey(): string|int|null
    {
        return $this->key;
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
     * @throws CodableException
     */
    public function encode(mixed $value, ?string $type = null): void
    {
        $expectedType = $type ?? '<non-resource>';
        $actualType = gettype($value);
        $type = $type ?? $actualType;

        if (in_array($type, ['null', 'NULL']) && is_null($value)) {
            $this->encodeNull();
        } elseif ($type === 'string' && is_string($value)) {
            $this->encodeString($value);
        } elseif (in_array($type, ['int', 'integer']) && is_int($value)) {
            $this->encodeInt($value);
        } elseif (in_array($type, ['bool', 'boolean']) && is_bool($value)) {
            $this->encodeBool($value);
        } elseif (in_array($type, ['float', 'double']) && is_float($value)) {
            $this->encodeFloat($value);
        } elseif ($type === 'object' && is_object($value)) {
            $this->encodeObject($value);
        } elseif ($type === 'array' && is_array($value)) {
            $this->encodeArray($value);
        } else {
            // can only happen for resources
            throw new ValueTypeMismatchException($this->getPath(), $actualType, $expectedType);
        }
    }

    public function encodeNull(): void
    {
        $this->value = null;
    }

    public function encodeString(?string $value): void
    {
        $this->value = $value;
    }

    public function encodeInt(?int $value): void
    {
        $this->value = $value;
    }

    public function encodeFloat(?float $value): void
    {
        $this->value = $value;
    }

    public function encodeBool(?bool $value): void
    {
        $this->value = $value;
    }

    /**
     * @throws DateTimeFormatException
     */
    public function encodeDateTime(?DateTimeInterface $value, string $format = null, DateTimeZone $tz = null): void
    {
        if (is_null($value)) {
            $this->value = $value;
            return;
        }

        $format = $format ?: $this->getContext()->getDateTimeFormat();
        $tz = $tz ?: $this->getContext()->getDateTimeZone();
        try {
            $dateTime = DateTimeImmutable::createFromInterface($value)->setTimezone($tz);
            $string = $dateTime->format($format);
            $this->encodeString($string);
        } catch (Exception $e) {
            throw new DateTimeFormatException($this->getPath(), $format, previous: $e);
        }
    }

    /**
     * @throws CodableException
     */
    private function encodeObjectOrEnumUsingDelegate(object $value): bool
    {
        $delegate = $this->getContext()->getDelegate(get_class($value));
        if ($delegate === null) {
            return false;
        }

        if ($delegate instanceof EncodableDelegate) {
            $delegate->encode($value, $this);
            return true;
        }

        if (is_callable($delegate)) {
            call_user_func($delegate, $value, $this);
            return true;
        }

        if (is_a($delegate, StaticEncodableDelegate::class, true)) {
            $delegate::encode($value, $this);
            return true;
        }

        return false;
    }

    /**
     * @throws CodableException
     */
    private function encodeObjectOrEnumUsingEncoder(object $value): bool
    {
        if ($this->encodeObjectOrEnumUsingDelegate($value)) {
            return true;
        }

        if ($value instanceof Encodable) {
            $value->encode($this);
            return true;
        }

        return false;
    }

    /**
     * @throws CodableException
     */
    public function encodeObject(?object $value): void
    {
        if ($value instanceof UnitEnum) {
            $this->encodeEnum($value);
            return;
        }

        if (is_null($value)) {
            $this->value = $value;
            return;
        }

        if ($this->encodeObjectOrEnumUsingEncoder($value)) {
            return;
        }

        // no encoder available for object class, check if iterable
        if (is_iterable($value)) {
            $this->encodeArray($value);
            return;
        }

        // no encoder available for object class, fallback to JSON based serialization
        try {
            $this->value = json_decode(
                json_encode($value, JSON_THROW_ON_ERROR),
                $this->getContext()->useAssociativeArraysForObjects(),
                flags: JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            throw new CodablePathException(
                $this->getPath(),
                "Cannot encode object at path '" . CodablePathException::convertPathToString($this->getPath()) . "'",
                previous: $e
            );
        }
    }

    /**
     * @throws CodableException
     */
    public function encodeEnum(?UnitEnum $value): void
    {
        if (is_null($value)) {
            $this->value = $value;
            return;
        }

        if ($this->encodeObjectOrEnumUsingEncoder($value)) {
            return;
        }

        if ($value instanceof BackedEnum) {
            $this->value = $value->value;
            return;
        }

        $this->value = $value->name;
    }

    public function encodeArray(?iterable $value, ?callable $iterator = null): void
    {
        if (is_null($value)) {
            $this->value = null;
            return;
        }

        if (!is_callable($iterator)) {
            $iterator = fn ($c, $v) => $c->encode($v);
        }

        $arr = [];
        foreach ($value as $k => $v) {
            $arr[$k] = null;
            $c = new self($arr[$k], $this->getContext(), $this, $k);
            $iterator($c, $v);
        }

        $this->value = $arr;
    }

    public function contains(string $key): bool
    {
        if (is_object($this->value)) {
            return property_exists($this->value, $key);
        } elseif (is_array($this->value)) {
            return array_key_exists($key, $this->value);
        } else {
            return false;
        }
    }

    public function nestedContainer(string $key): EncodingContainer
    {
        if ($this->getContext()->useAssociativeArraysForObjects()) {
            if (!is_array($this->value)) {
                $this->value = [];
            }

            $nestedValue = &$this->value[$key];
        } else {
            if (!is_object($this->value)) {
                $this->value = new stdClass();
            }

            $nestedValue = &$this->value->$key;
        }

        return new self($nestedValue, $this->getContext()->createChildContext(), $this, $key);
    }

    public function nestedContainerForPath(array $path): EncodingContainer
    {
        $container = $this;
        foreach ($path as $key) {
            $container = $container->nestedContainer($key);
        }

        return $container;
    }

    public function nestedContainerForPathIfExists(array $path): ?EncodingContainer
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

    public function remove(): bool
    {
        $key = $this->getKey();
        $parent = $this->getParent();

        if ($key === null || $parent === null) {
            return false;
        }

        unset($parent->$key);
        return true;
    }

    /**
     * Check if the key exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return $this->contains($key);
    }

    /**
     * Get nested container for the given key.
     *
     * @param string $key
     *
     * @return EncodingContainer
     */
    public function __get(string $key): EncodingContainer
    {
        return $this->nestedContainer($key);
    }

    /**
     * @throws CodableException
     */
    public function __set(string $key, mixed $value)
    {
        $this->nestedContainer($key)->encode($value);
    }

    public function __unset(string $key): void
    {
        if (is_object($this->value)) {
            unset($this->value->$key);
        } elseif (is_array($this->value)) {
            unset($this->value[$key]);
        }
    }
}
