<?php

declare(strict_types=1);

namespace PhpMyAdmin\Helpers;

use Throwable;

class Notifier
{
    /**
     * @param string $message
     * @return void
     */
    private static function notify(string $message): void
    {
        dump($message);
    }

    /**
     * @param Throwable|string|mixed $message
     * @return string
     */
    private static function message($message): string
    {
        if (is_string($message)) {
            return $message;
        }

        if ($message instanceof Throwable) {
            dd($message);
        }

        return 'Unexpected message';
    }

    /**
     * @param Throwable|string $warning
     * @return void
     */
    public static function warning($warning): void
    {
        $message = self::message($warning);
        self::notify($message);
    }

    /**
     * @param Throwable|string $error
     * @return void
     */
    public static function error($error): void
    {
        $message = self::message($error);
        self::notify($message);
        die();
    }
}

