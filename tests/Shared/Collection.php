<?php

namespace MinVWS\Tests\Codable\Shared;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * @template T
 */
class Collection implements ArrayAccess, IteratorAggregate
{
    private array $array = [];

    public static function forArray(array $arr): self
    {
        $collection = new self();
        $collection->array = $arr;
        return $collection;
    }

    public function offsetExists(mixed $offset): bool
    {
        return (is_int($offset) || is_string($offset)) && array_key_exists($offset, $this->array);
    }

    /**
     * @return T|null
     */
    public function &offsetGet(mixed $offset): mixed
    {
        return $this->array[$offset];
    }

    /**
     * @param T $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->array[] = $value;
        } else {
            $this->array[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->array[$offset]);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->array);
    }

    public function toArray(): array
    {
        return $this->array;
    }
}
