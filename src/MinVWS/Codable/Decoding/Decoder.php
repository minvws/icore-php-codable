<?php

namespace MinVWS\Codable\Decoding;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

class Decoder
{
    private readonly DecodingContext $context;
    private readonly DecodableDelegate $dateTimeDelegate;

    public function __construct(?DecodingContext $context = null)
    {
        $this->context = $context ?? new DecodingContext();

        $this->dateTimeDelegate = new class implements DecodableDelegate {
            /**
             * @param class-string<DateTime|DateTimeImmutable> $class
             */
            public function decode(string $class, DecodingContainer $container, ?object $object = null): DateTime|DateTimeImmutable
            {
                return $container->decodeDateTime(class: $class);
            }
        };

        $this->context->registerDelegate(DateTimeInterface::class, $this->dateTimeDelegate);
    }

    public function getContext(): DecodingContext
    {
        return $this->context;
    }

    /**
     * Creates a decoding container for decoding the given data.
     */
    public function decode(mixed $data): DecodingContainer
    {
        return new DecodingContainer($data, $this->context);
    }
}
