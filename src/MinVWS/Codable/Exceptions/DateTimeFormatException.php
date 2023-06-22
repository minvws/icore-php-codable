<?php

namespace MinVWS\Codable\Exceptions;

class DateTimeFormatException extends CodablePathException
{
    public function __construct(array $path, string $format)
    {
        parent::__construct($path, "Invalid date/time format at path '" . static::convertPathToString($path) . "', format '{$format}'");
    }
}
