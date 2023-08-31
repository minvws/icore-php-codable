<?php

namespace MinVWS\Codable\Exceptions;

class PathNotFoundException extends CodablePathException
{
    public function __construct(array $path)
    {
        parent::__construct($path, "Path not found '" . static::convertPathToString($path) . "'");
    }
}
