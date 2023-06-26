<?php

namespace MinVWS\Codable\Coding;

use DateTimeZone;

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

    public static function create(string $key): CodingKey
    {
        return new self($key);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
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
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Sets the property type.
     *
     * This can either be a built-in PHP type or a class name.
     *
     * @return $this
     */
    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function optional(bool $optional = true): self
    {
        $this->optional = $optional;
        return $this;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function readOnly(bool $readOnly = true): self
    {
        $this->readOnly = $readOnly;
        return $this;
    }

    /**
     * Returns the element type in case the property is of type array.
     */
    public function getElementType(): ?string
    {
        return $this->elementType;
    }

    /**
     * Sets the element type in case the property is of type array.
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
     */
    public function getDateTimeFormat(): ?string
    {
        return $this->dateTimeFormat;
    }

    /**
     * Sets the date time format in case the property is of type DateTime.
     *
     * @return $this
     */
    public function dateTimeFormat(string $format): self
    {
        $this->dateTimeFormat = $format;
        return $this;
    }

    /**
     * Returns the date time format in case the property is of type DateTime.
     */
    public function getDateTimeZone(): ?DateTimeZone
    {
        return $this->dateTimeZone;
    }

    /**
     * Sets the date time zone in case the property is of type DateTime.
     *
     * @return $this
     */
    public function dateTimeZone(DateTimeZone $zone): self
    {
        $this->dateTimeZone = $zone;
        return $this;
    }
}
