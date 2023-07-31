<?php

declare(strict_types=1);

namespace PhpMyAdmin\Stores;

use Closure;

abstract class Store
{
    /**
     * @var array<string, Store>
     */
    private static array $instances = [];

    public static function instance(): Store
    {
        $instance = static::class;
        if (!isset(self::$instances[$instance])) {
            self::$instances[$instance] = new static();
        }

        return self::$instances[$instance];
    }
}
