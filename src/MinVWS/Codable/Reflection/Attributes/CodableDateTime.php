<?php

declare(strict_types=1);

namespace MinVWS\Codable\Reflection\Attributes;

use Attribute;
use DateTimeZone;
use Exception;

#[Attribute]
readonly class CodableDateTime
{
    public ?DateTimeZone $timeZone;

    /**
     * @throws Exception
     */
    public function __construct(
        public ?string $format = null,
        string|null $timeZone = null
    ) {
        $this->timeZone = is_string($timeZone) ? new DateTimeZone($timeZone) : null;
    }
}
