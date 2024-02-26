<?php

namespace MinVWS\Codable\Coding;

/**
 * Context base.
 *
 * Can be used to register delegates and
 * contextual values for encoding/decoding.
 *
 * @package MinVWS\Codable
 */
abstract class Context
{
    /**
     * @var string|null
     */
    private ?string $mode = null;

    /**
     * @var array
     */
    private array $values = [];

    /**
     * @var array<class-string, class-string|object|callable>
     */
    private array $delegates = [];

    /**
     * Constructor.
     *
     * @param static|null $parent
     */
    public function __construct(private readonly ?self $parent = null)
    {
        $this->init();
    }

    protected function init(): void
    {
    }

    /**
     * Optional mode.
     *
     * @param string|null $mode
     */
    public function setMode(?string $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * Returns the optional mode.
     *
     * @return string|null
     */
    public function getMode(): ?string
    {
        if (isset($this->mode)) {
            return $this->mode;
        } elseif ($this->parent !== null) {
            return $this->parent->getMode();
        } else {
            return null;
        }
    }

    /**
     * Returns the root context.
     *
     * @return static|null
     */
    public function getRoot(): ?static
    {
        if ($this->getParent() !== null) {
            return $this->getParent()->getRoot();
        }

        return $this;
    }

    /**
     * Returns the parent context.
     */
    public function getParent(): ?static
    {
        return $this->parent;
    }

    /**
     * Returns the context value for the given key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getValue(string $key): mixed
    {
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        } elseif ($this->parent !== null) {
            return $this->parent->getValue($key);
        } else {
            return null;
        }
    }

    /**
     * Sets the context value for the given key.
     */
    public function setValue(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    /**
     * Unset value.
     *
     * This is different from setting the value to null as that would prevent the context asking
     * its parent for a value. By unsetting the value the context will try if its parent context
     * has a value if requested by getValue.
     */
    public function unsetValue(string $key): void
    {
        unset($this->values[$key]);
    }

    /**
     * Returns the delegate for the given class (if registered).
     *
     * @param string $class
     *
     * @return class-string|object|callable|null
     */
    public function getDelegate(string $class)
    {
        $keys = $this->getDelegateKeysForClass($class);
        $delegates = $this->getDelegates();

        foreach ($keys as $key) {
            if (isset($delegates[$key])) {
                return $delegates[$key];
            }
        }

        return null;
    }

    /**
     * Returns all the possible delegate keys for the given class ordered by importance
     * (e.g. the class itself, but also parent classes and interfaces it implements).
     *
     * @param string $class
     *
     * @return string[]
     */
    private function getDelegateKeysForClass(string $class): array
    {
        if (!class_exists($class) && !interface_exists($class)) {
            return [];
        }

        $keys = [$class];
        $keys = array_merge($keys, class_parents($class));
        $keys = array_merge($keys, class_implements($class));
        return $keys;
    }

    /**
     * Merge delegates.
     *
     * @return array
     */
    private function getDelegates(): array
    {
        if ($this->getParent() === null) {
            return $this->delegates;
        } else {
            return array_merge($this->getParent()->getDelegates(), $this->delegates);
        }
    }

    /**
     * Register external delegate for the given class.
     *
     * @param class-string                 $class     Class name of the class for which we need a delegate.
     * @param class-string|object|callable $delegate Class name of the delegate class, instance or callable.
     */
    public function registerDelegate(string $class, string|object|callable $delegate): void
    {
        $this->delegates[$class] = $delegate;
    }

    /**
     * Unregister delegate for the given class.
     *
     * @param class-string $class
     */
    public function unregisterDelegate(string $class): void
    {
        unset($this->delegates[$class]);
    }

    abstract public function createChildContext(): self;
}
