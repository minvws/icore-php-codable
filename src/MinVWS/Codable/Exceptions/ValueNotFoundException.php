<?php

namespace MinVWS\Codable\Exceptions;

class ValueNotFoundException extends CodablePathException
{
    public function __construct(array $path)
    {
        parent::__construct($path, "Value not found at path '" . static::convertPathToString($path) . "'");
    }
}
