<?php

namespace MinVWS\Codable\Reflection;

use ArgumentCountError;
use Exception;
use MinVWS\Codable\Exceptions\CodableException;
use ReflectionClass;
use ReflectionException;

/**
 * @template T of object
 */
class ReflectionCodableClass
{
    /**
     * @var array<class-string, self> $cache
     */
    private static array $cache = [];

    private ReflectionClass $refClass;

    /**
     * @var array<string, ReflectionCodableProperty>|null
     */
    private ?array $properties;

    /**
     * @param class-string<T> $class
     *
     * @throws ReflectionException
     */
    private function __construct(private string $class)
    {
        $this->refClass = new ReflectionClass($class);
    }

    /**
     * @param class-string<TClass> $class
     *
     * @return self<TClass>
     *
     * @throws CodableException
     *
     * @template TClass of object
     */
    public static function forClass(string $class): self
    {
        if (!isset(self::$cache[$class])) {
            try {
                self::$cache[$class] = new self($class);
            } catch (ReflectionException $e) {
                throw new CodableException(sprintf("Invalid class \'%s'", $class), previous: $e);
            }
        }

        return self::$cache[$class];
    }

    public function getReflectionClass(): ReflectionClass
    {
        return $this->refClass;
    }

    /**
     * @return array<ReflectionCodableProperty>
     */
    public function getProperties(): array
    {
        if (!isset($this->properties)) {
            $this->properties = [];
            foreach ($this->refClass->getProperties() as $property) {
                $this->properties[$property->getName()] = new ReflectionCodableProperty($this, $property);
            }
        }

        return $this->properties;
    }

    public function getProperty(string $propertyName): ReflectionCodableProperty
    {
        return $this->getProperties()[$propertyName];
    }

    /**
     * @param array $args
     *
     * @return T
     *
     * @throws CodableException
     */
    public function newInstanceArgs(array $args): object
    {
        $sortedArgs = [];
        foreach ($this->getReflectionClass()->getConstructor()?->getParameters() ?? [] as $parameter) {
            if (array_key_exists($parameter->getName(), $args)) {
                $sortedArgs[] = $args[$parameter->getName()];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $sortedArgs[] = $parameter->getDefaultValue();
            } elseif ($parameter->isOptional() || $parameter->getType()?->allowsNull()) {
                $sortedArgs[] = null;
            } else {
                throw new CodableException(
                    sprintf(
                        'Error creating instance for class "%s", arg "%s" missing',
                        $this->class,
                        $parameter->getName()
                    )
                );
            }
        }

        try {
            $instance = $this->refClass->newInstanceArgs($sortedArgs);
            assert(is_a($instance, $this->class));
            return $instance;
        } catch (Exception $e) {
            throw new CodableException(
                sprintf(
                    'Error creating instance for class "%s": %s',
                    $this->class,
                    $e->getMessage()
                ),
                previous: $e
            );
        }
    }
}
