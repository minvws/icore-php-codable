<?php

namespace MinVWS\Codable\Exceptions;

class KeyNotFoundException extends CodablePathException
{
    public function __construct(array $path)
    {
        parent::__construct($path, "Key not found at path '" . static::convertPathToString($path) . "'");
    }
}
