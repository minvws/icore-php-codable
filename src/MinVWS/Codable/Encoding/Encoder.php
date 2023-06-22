<?php

namespace MinVWS\Codable\Encoding;

use DateTimeInterface;
use MinVWS\Codable\Exceptions\ValueTypeMismatchException;
use Traversable;

class Encoder
{
    /**
     * @var EncodingContext
     */
    private readonly EncodingContext $context;

    /**
     * @var EncodableDelegate
     */
    private readonly EncodableDelegate $dateTimeDelegate;

    /**
     * @var EncodableDelegate
     */
    private readonly EncodableDelegate $traversableDelegate;

    /**
     * Constructor.
     *
     * @param EncodingContext|null $context
     */
    public function __construct(?EncodingContext $context = null)
    {
        $this->context = $context ?? new EncodingContext();

        $this->dateTimeDelegate = new class implements EncodableDelegate {
            /**
             * @inheritDoc
             */
            public function encode(object $value, EncodingContainer $container): void
            {
                assert($value instanceof DateTimeInterface);
                $container->encodeDateTime($value);
            }
        };

        $this->traversableDelegate = new class implements EncodableDelegate {
            /**
             * @inheritDoc
             */
            public function encode(object $value, EncodingContainer $container): void
            {
                assert($value instanceof Traversable);
                $container->encodeArray(iterator_to_array($value));
            }
        };

        $this->context->registerDelegate(DateTimeInterface::class, $this->dateTimeDelegate);
        $this->context->registerDelegate(Traversable::class, $this->traversableDelegate);
    }

    public function getContext(): EncodingContext
    {
        return $this->context;
    }

    /**
     * Encodes the given data
     *
     * @throws ValueTypeMismatchException
     */
    public function encode(mixed $data): mixed
    {
        $value = null;
        $container = new EncodingContainer($value, $this->context);
        $container->encode($data);
        return $value;
    }
}
