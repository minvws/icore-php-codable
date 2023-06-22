<?php

namespace MinVWS\Codable\Coding;

use DateTimeZone;

/**
 * Coding key.
 *
 * @package MinVWS\Codable
 */
class CodingKey
{
    private string $key;
    private string $name;
    private ?string $type = null;
    private bool $optional = false;
    private bool $readOnly = false;
    private ?string $elementType = null;
    private ?string $dateTimeFormat = null;
    private ?DateTimeZone $dateTimeZone = null;

    /**
     * Constructor.
     *
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->key = $key;
        $this->name = $key;
    }

    /**
     * Returns the object property name.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Create.
     *
     * @param string $key
     *
     * @return CodingKey
     */
    public static function create(string $key): CodingKey
    {
        return new self($key);
    }

    /**
     * Returns the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set name of the serialized property.
     *
     * @param string $name
     *
     * @return $this
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Returns the property type.
     *
     * This can either be a built-in PHP type or a class name.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Sets the property type.
     *
     * @param string $type
     *
     * @return $this
     */
    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Returns if this property is optional.
     *
     * @return bool
     */
    public function isOptional(): bool
    {
        return $this->optional;
    }

    /**
     * Mark/unmark property as optional.
     *
     * @param bool $optional
     *
     * @return $this
     */
    public function optional(bool $optional = true): self
    {
        $this->optional = $optional;
        return $this;
    }

    /**
     * Returns if this property is read-only.
     */
    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    /**
     * Mark/unmark property as read-only.
     *
     * @param bool $readOnly
     *
     * @return $this
     */
    public function readOnly(bool $readOnly = true): self
    {
        $this->readOnly = $readOnly;
        return $this;
    }

    /**
     * Returns the element type in case the property is of type array.
     *
     * @return string|null
     */
    public function getElementType(): ?string
    {
        return $this->elementType;
    }

    /**
     * Sets the element type in case the property is of type array.
     *
     * @param string $elementType
     *
     * @return $this
     */
    public function elementType(string $elementType): self
    {
        $this->elementType = $elementType;
        return $this;
    }

    /**
     * Returns the date time format in case the property is of type DateTime.
     *
     * @return string|null
     */
    public function getDateTimeFormat(): ?string
    {
        return $this->dateTimeFormat;
    }

    /**
     * Sets the date time format in case the property is of type DateTime.
     *
     * @param string $format
     *
     * @return self
     */
    public function dateTimeFormat(string $format): self
    {
        $this->dateTimeFormat = $format;
        return $this;
    }

    /**
     * Returns the date time format in case the property is of type DateTime.
     *
     * @return DateTimeZone|null
     */
    public function getDateTimeZone(): ?DateTimeZone
    {
        return $this->dateTimeZone;
    }

    /**
     * Sets the date time zone in case the property is of type DateTime.
     *
     * @param DateTimeZone $zone
     *
     * @return self
     */
    public function dateTimeZone(DateTimeZone $zone): self
    {
        $this->dateTimeZone = $zone;
        return $this;
    }
}

/**
 * Create coding key.
 *
 * @param string $key
 *
 * @return CodingKey
 */
function CodingKey(string $key): CodingKey
{
    return CodingKey::create($key);
}
