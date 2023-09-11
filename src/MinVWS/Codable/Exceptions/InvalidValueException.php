<?php

namespace MinVWS\Codable\Exceptions;

use Throwable;

class InvalidValueException extends CodablePathException
{
    public function __construct(array $path, mixed $value, string $type, Throwable $previous = null)
    {
        parent::__construct(
            $path,
            sprintf(
                "Invalid %s at path '%s', expected value compatible with type: '%s'",
                is_scalar($value) ? "value '{$value}'" : 'value',
                static::convertPathToString($path),
                $type
            ),
            previous: $previous
        );
    }
}
