<?php

namespace Tabuna\Map;

use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Mapper
{
    /**
     * The source data to be mapped. Can be an object, array, or collection.
     */
    protected mixed $source;

    /**
     * Indicates whether the source should be treated as a collection of items.
     */
    protected bool $isCollection = false;

    /**
     * A list of mappers to apply. Each item must be either:
     * - a string (class name with a `map` method), or
     * - a callable that receives ($this, $item).
     *
     * @var array<int, string|callable>
     */
    protected array $mappers = [];

    /**
     * The IoC container used to resolve mappers and target classes.
     */
    protected Container $container;

    /**
     * @param mixed          $source    The data source to map from.
     * @param Container|null $container Optional dependency container.
     */
    public function __construct(mixed $source, ?Container $container = null)
    {
        $this->container = $container ?? Container::getInstance();

        if ($source instanceof Arrayable) {
            $this->source = $source->all();
        } else {
            $this->source = $source;
        }
    }

    /**
     * Create a new Mapper instance.
     *
     * @param mixed $source
     *
     * @return static
     */
    public static function map(mixed $source): self
    {
        return new self($source);
    }

    /**
     * Enable collection mode (map each item in iterable).
     *
     * @return $this
     */
    public function collection(): self
    {
        $this->isCollection = true;

        return $this;
    }

    /**
     * Set the mappers to be applied (class names or callables).
     *
     * @param array|callable|string ...$mappers
     *
     * @return $this
     */
    public function with(...$mappers): self
    {
        $this->mappers = $mappers;

        return $this;
    }

    /**
     * Perform mapping to target class or object.
     *
     * @param class-string $targetClass
     *
     * @return mixed|Collection
     */
    public function to(string $targetClass)
    {
        if ($this->isCollection) {
            return collect($this->source)
                ->map(fn ($item) => $this->mapItem($item, $targetClass));
        }

        return $this->mapItem($this->source, $targetClass);
    }

    /**
     * Map a single item to the target class.
     *
     * @param mixed        $item
     * @param class-string $targetClass
     *
     * @return mixed
     */
    protected function mapItem(mixed $item, string $targetClass): mixed
    {
        foreach ($this->mappers as $mapper) {
            if (is_string($mapper)) {
                $mapper = $this->container->make($mapper);
            }

            if (is_callable($mapper)) {
                return $mapper($this, $item);
            }

            throw new LogicException('Each mapper must be a class name or a callable.');
        }

        $target = $this->container->make($targetClass);

        return $this->fill($target, $item);
    }

    /**
     * Fill the target object with data from the item.
     *
     * @param object $target
     * @param mixed  $item
     *
     * @return object
     */
    protected function fill(object $target, mixed $item): object
    {
        $attributes = is_array($item)
            ? $item
            : (array) $item;

        if ($target instanceof Model) {
            return $target->fill($attributes);
        }

        foreach ($attributes as $key => $value) {
            if (property_exists($target, $key)) {
                $target->$key = $value;
            }
        }

        return $target;
    }

    /**
     * Get the mapped result as a plain array.
     *
     * @return array
     */
    public function toArray(): array
    {
        if ($this->isCollection) {
            return collect($this->source)
                ->map(fn ($item) => is_array($item) ? $item : (array) $item)
                ->toArray();
        }

        return is_array($this->source) ? $this->source : (array) $this->source;
    }

    /**
     * Get the mapped result as a JSON string.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
