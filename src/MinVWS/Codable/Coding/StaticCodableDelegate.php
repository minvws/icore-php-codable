<?php

namespace MinVWS\Codable\Coding;

use MinVWS\Codable\Decoding\StaticDecodableDelegate;
use MinVWS\Codable\Encoding\StaticEncodableDelegate;

/**
 * External encode/decode implementation for class.
 *
 * @package MinVWS\Codable
 */
interface StaticCodableDelegate extends StaticEncodableDelegate, StaticDecodableDelegate
{
}
