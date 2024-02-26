<?php

namespace MinVWS\Codable\Exceptions;

class ReadOnlyException extends CodableException
{
    public function __construct()
    {
        parent::__construct("Decoding container is read-only");
    }
}
