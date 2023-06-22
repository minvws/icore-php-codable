<?php

namespace MinVWS\Codable\Exceptions;

class ValueTypeMismatchException extends CodablePathException
{
    public function __construct(array $path, string $type, string $expectedType)
    {
        parent::__construct($path, "Unexpected value type '{$type}' at path '" . static::convertPathToString($path) . "', expected type: '$expectedType'");
    }
}
