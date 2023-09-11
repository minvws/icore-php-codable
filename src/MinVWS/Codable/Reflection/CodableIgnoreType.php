<?php

declare(strict_types=1);

namespace MinVWS\Codable\Reflection;

enum CodableIgnoreType
{
    case Always;
    case OnEncode;
    case OnDecode;
}
