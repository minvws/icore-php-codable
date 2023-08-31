<?php

namespace MinVWS\Codable\JSON;

use JsonException;
use MinVWS\Codable\Encoding\Encoder;
use MinVWS\Codable\Encoding\EncodingContext;
use MinVWS\Codable\Exceptions\ValueTypeMismatchException;

/**
 * JSON encoder.
 *
 * @package MinVWS\Codable
 */
class JSONEncoder
{
    /**
     * @var Encoder
     */
    private Encoder $encoder;

    /**
     * Constructor.
     *
     * @param EncodingContext|null $context
     */
    public function __construct(?EncodingContext $context = null)
    {
        $this->encoder = new Encoder($context);
    }

    /**
     * Returns the encoder.
     *
     * @return Encoder
     */
    public function getEncoder(): Encoder
    {
        return $this->encoder;
    }

    /**
     * Returns the context.
     *
     * @return EncodingContext
     */
    public function getContext(): EncodingContext
    {
        return $this->encoder->getContext();
    }

    /**
     * Encode to JSON string.
     *
     * @param mixed         $data Data.
     * @param int           $options JSON options (see json_encode)
     * @param int<1, max>   $depth
     *
     * @return string JSON
     *
     * @throws JsonException
     * @throws ValueTypeMismatchException
     */
    public function encode(mixed $data, int $options = 0, int $depth = 512): string
    {
        $encodedData = $this->encoder->encode($data);
        return json_encode($encodedData, $options | JSON_THROW_ON_ERROR, $depth);
    }
}
