<?php

declare(strict_types=1);

namespace PhpMyAdmin\Model;

use PhpMyAdmin\Helpers\Notifier;
use ReflectionClass;
use ReflectionObject;
use ReflectionProperty;
use Throwable;

abstract class Model
{
    protected array $casts = [];
    protected array $array_casts = [];

    /**
     * @param array $data
     * @return $this
     */
    public function fill(array $data): self
    {
        try {
            $reflection = new ReflectionObject($this);

            foreach ($data as $key => $value) {
                if (!property_exists($this, $key)) {
                    continue;
                }

                if (array_key_exists($key, $this->casts)) {
                    $value = $this->cast($key, $value, $data);
                }

                if (array_key_exists($key, $this->array_casts)) {
                    $value = is_array($value) ? array_map(function ($item) use ($key) {
                        $caster = $this->array_casts[$key];
                        return class_exists($caster) ? (new $caster)->fill($item) : $item;
                    }, $value) : $value;
                }

                if (is_null($value) && !$reflection->getProperty($key)->getType()->allowsNull()) {
                    continue;
                }

                $this->{$key} = $value;
            }

        } catch (Throwable $error) {
            Notifier::error($error);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $data = self::classToArray($this);

        foreach ($this->array_casts as $key => $cast) {
            $data[$key] = is_array($data[$key]) ? array_map(function ($item) {
                return is_subclass_of($item, Model::class) ? $item->toArray() : $item;
            }, $data[$key]) : $data[$key];
        }

        return $data;
    }

    /**
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * @param string $key
     * @param $value
     * @param array $attributes
     * @return mixed
     */
    protected function cast(string $key, $value, array $attributes)
    {
        $caster = $this->casts[$key];
        return class_exists($caster) ? (new $caster)->get($this, $key, $value, $attributes) : $value;
    }

    /**
     * @param $model
     * @param string $key
     * @param $value
     * @param array $attributes
     * @return Model|null
     */
    public function get($model, string $key, $value, array $attributes): ?Model
    {
        return (new static)->fill($this->prepareCastValue($value));
    }

    protected function prepareCastValue($value): array
    {
        return (is_array($value) ? $value : json_decode($value, true)) ?: [];
    }

    /**
     * @param Model $object
     * @return array
     */
    public static function classToArray(Model $object): array
    {
        $class = get_class($object);
        $attrs = get_class_vars($class);
        $values = get_object_vars($object);

        $result = [];
        $data = array_merge($attrs, $values);

        foreach (self::getPublicProps($object) as $prop) {
            $result[$prop] = method_exists($data[$prop], 'toArray') ? $data[$prop]->toArray() : $data[$prop];
        }

        return $result;
    }

    /**
     * @param object $object
     * @return array
     */
    public static function getPublicProps(object $object): array
    {
        $reflect = new ReflectionClass($object);
        return array_map(function (ReflectionProperty $prop) {
            return $prop->name;
        }, $reflect->getProperties(ReflectionProperty::IS_PUBLIC));
    }
}

