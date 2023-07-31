<?php

declare(strict_types=1);

namespace PhpMyAdmin\Model;

class ServerCreds extends ModelValid
{
    public string $user;

    public string $password;

    protected array $requiredFields = [
        'user',
        'password'
    ];
}
