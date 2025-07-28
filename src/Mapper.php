<?php

namespace Tabuna\Map;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class Mapper
{
    protected mixed $source;
    protected bool $isCollection = false;
    protected array $mappers = [];
    protected Container $container;

    public function __construct(mixed $source, ?Container $container = null)
    {
        $this->container = $container ?? Container::getInstance();

        if ($source instanceof Request) {
            $this->source = $source->all();
        } else {
            $this->source = $source;
        }
    }

    public static function map(mixed $source): self
    {
        return new self($source);
    }

    public function collection(): self
    {
        $this->isCollection = true;

        return $this;
    }

    /**
     * @param array|callable|string ...$mappers
     */
    public function with(...$mappers): self
    {
        $this->mappers = $mappers;

        return $this;
    }

    /**
     * Основной метод маппинга в экземпляр класса $targetClass.
     *
     * @param class-string $targetClass
     *
     * @return mixed|Collection
     */
    public function to(string $targetClass)
    {
        if ($this->isCollection && is_iterable($this->source)) {
            $result = [];
            foreach ($this->source as $item) {
                $result[] = $this->mapItem($item, $targetClass);
            }

            return collect($result);
        }

        return $this->mapItem($this->source, $targetClass);
    }

    protected function mapItem(mixed $item, string $targetClass): mixed
    {
        if (! empty($this->mappers)) {
            $result = null;
            foreach ($this->mappers as $mapper) {
                if (is_string($mapper)) {
                    $mapperInstance = $this->container->make($mapper);
                    if (! method_exists($mapperInstance, 'map')) {
                        throw new \LogicException("Mapper class {$mapper} must have method map()");
                    }
                    $result = $mapperInstance->map($item, $targetClass);
                } elseif (is_callable($mapper)) {
                    $result = $mapper($this, $item);
                } else {
                    throw new \LogicException('Mapper must be a class name or a callable');
                }
            }

            return $result;
        }

        $target = $this->container->make($targetClass);

        if ($target instanceof Model) {
            $attributes = is_array($item) ? $item : (array) $item;
            $target->fill($attributes);

            return $target;
        }

        if (is_array($item)) {
            foreach ($item as $key => $value) {
                if (property_exists($target, $key)) {
                    $target->$key = $value;
                }
            }
        } elseif (is_object($item)) {
            foreach (get_object_vars($item) as $key => $value) {
                if (property_exists($target, $key)) {
                    $target->$key = $value;
                }
            }
        }

        return $target;
    }

    public function toArray(): array
    {
        if ($this->isCollection && is_iterable($this->source)) {
            return collect($this->source)->map(function ($item) {
                return is_array($item) ? $item : (array) $item;
            })->toArray();
        }

        return is_array($this->source) ? $this->source : (array) $this->source;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
