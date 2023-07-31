<?php

declare(strict_types=1);

namespace PhpMyAdmin\Model;

abstract class ModelValid extends Model
{
    protected array $requiredFields = [];

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->validateConfig($config);
        $this->setupConfig($config);
    }

    /**
     * @param array $config
     * @return void
     */
    protected function validateConfig(array $config)
    {
        foreach ($this->requiredFields as $requiredField) {
            empty($config[$requiredField]) && die(static::class . " config should contain $requiredField setting");
        }
    }

    /**
     * @param array $config
     * @return void
     */
    protected function setupConfig(array $config)
    {
        $this->fill($config);
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
        return class_exists($caster) ? (new $caster($this->prepareCastValue($value)))->get($this, $key, $value, $attributes) : $value;
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
        return new static($this->prepareCastValue($value));
    }
}

