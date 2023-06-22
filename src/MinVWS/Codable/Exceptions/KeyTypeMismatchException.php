<?php

namespace MinVWS\Codable\Exceptions;

class KeyTypeMismatchException extends CodablePathException
{
    public function __construct(array $path, string $type, string $expectedType)
    {
        parent::__construct($path, "Unexpected key type '{$type}' at path '" . static::convertPathToString($path) . "', expected type: '$expectedType'");
    }
}
