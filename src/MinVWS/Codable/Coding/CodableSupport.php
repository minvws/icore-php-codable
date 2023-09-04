<?php

declare(strict_types=1);

namespace MinVWS\Codable\Coding;

use MinVWS\Codable\Decoding\DecodableSupport;
use MinVWS\Codable\Encoding\EncodableSupport;

trait CodableSupport
{
    use EncodableSupport;
    use DecodableSupport;
}
