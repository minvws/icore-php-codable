<?php

namespace MinVWS\Codable\Encoding;

use DateTimeZone;
use MinVWS\Codable\Coding\Context;

/**
 * Encoding context.
 *
 * Can be used to register encodable delegates and
 * contextual values for encoding.
 */
class EncodingContext extends Context
{
    public const MODE_OUTPUT  = 'output';
    public const MODE_STORE   = 'store';
    public const MODE_DISPLAY = 'display';
    public const MODE_EXPORT  = 'export';

    private const DEFAULT_DATE_TIME_FORMAT = 'Y-m-d\TH:i:sp';
    private const DEFAULT_DATE_TIME_ZONE   = 'UTC';

    private ?string $dateTimeFormat = null;
    private ?DateTimeZone $dateTimeZone = null;
    private ?bool $useAssociativeArraysForObjects = null;

    /**
     * Should objects be encoded as associative arrays instead of stdClass?
     *
     * Defaults to false.
     *
     * @return bool
     */
    public function useAssociativeArraysForObjects(): bool
    {
        if ($this->useAssociativeArraysForObjects !== null) {
            return $this->useAssociativeArraysForObjects;
        } elseif ($this->getParent() !== null) {
            return $this->getParent()->useAssociativeArraysForObjects();
        } else {
            return false;
        }
    }

    /**
     * Controls if objects should be encoded as associative arrays instead of stdClass.
     *
     * @param bool $useAssociativeArraysForObjects
     */
    public function setUseAssociativeArraysForObjects(bool $useAssociativeArraysForObjects): void
    {
        $this->useAssociativeArraysForObjects = $useAssociativeArraysForObjects;
    }

    /**
     * Returns the default date time format.
     *
     * @return string
     */
    public function getDateTimeFormat(): string
    {
        if ($this->dateTimeFormat) {
            return $this->dateTimeFormat;
        } elseif ($this->getParent() !== null) {
            return $this->getParent()->getDateTimeFormat();
        } else {
            return self::DEFAULT_DATE_TIME_FORMAT;
        }
    }

    /**
     * Sets the default date time format.
     *
     * @param string $dateTimeFormat
     */
    public function setDateTimeFormat(string $dateTimeFormat): void
    {
        $this->dateTimeFormat = $dateTimeFormat;
    }

    /**
     * Returns the default timezone.
     *
     * @return DateTimeZone
     */
    public function getDateTimeZone(): DateTimeZone
    {
        if ($this->dateTimeZone) {
            return $this->dateTimeZone;
        } elseif ($this->getParent() !== null) {
            return $this->getParent()->getDateTimeZone();
        } else {
            return new DateTimeZone(self::DEFAULT_DATE_TIME_ZONE);
        }
    }

    /**
     * Set the default timezone.
     *
     * @param DateTimeZone $dateTimeZone
     */
    public function setDateTimeZone(DateTimeZone $dateTimeZone): void
    {
        $this->dateTimeZone = $dateTimeZone;
    }

    public function createChildContext(): self
    {
        return new self($this);
    }
}
