<?php

namespace MinVWS\Codable\Exceptions;

use Throwable;

/**
 * Base exception class for encoding/decoding errors at a certain path.
 */
class CodablePathException extends CodableException
{
    /**
     * @param array<string|int> $path
     */
    public function __construct(private readonly array $path, string $message, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Convert path to string.
     *
     * @param array<string|int> $path
     *
     * @return string
     */
    public static function convertPathToString(array $path): string
    {
        $result = '';

        foreach ($path as $key) {
            if (is_int($key)) {
                $result .= "[{$key}]";
            } else {
                $result .= (strlen($result) > 0 ? '.' : '') . $key;
            }
        }

        return $result;
    }

    /**
     * @return array<string|int>
     */
    public function getPath(): array
    {
        return $this->path;
    }

    public function getPathAsString(): string
    {
        return static::convertPathToString($this->path);
    }
}
