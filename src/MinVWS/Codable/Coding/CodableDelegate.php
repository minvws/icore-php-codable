<?php

namespace MinVWS\Codable\Coding;

use MinVWS\Codable\Decoding\DecodableDelegate;
use MinVWS\Codable\Encoding\EncodableDelegate;

/**
 * External codable implementation for class.
 */
interface CodableDelegate extends EncodableDelegate, DecodableDelegate
{
}
