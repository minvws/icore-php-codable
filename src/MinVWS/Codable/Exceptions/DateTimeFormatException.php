<?php

namespace MinVWS\Codable\Exceptions;

use Throwable;

class DateTimeFormatException extends CodablePathException
{
    public function __construct(array $path, string $format, Throwable $previous = null)
    {
        parent::__construct(
            $path,
            "Invalid date/time format at path '" . static::convertPathToString($path) . "', format '{$format}'",
            previous: $previous
        );
    }
}
