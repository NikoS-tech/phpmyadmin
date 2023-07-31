<?php

declare(strict_types=1);

namespace PhpMyAdmin\Model;

use PhpMyAdmin\Config\Settings\Server as ServerSettings;

class Server extends ModelValid
{
    public string $host;

    public int $port = 3306;

    public ServerCreds $creds;

    protected ServerSettings $serverSettings;

    protected array $config;

    protected array $requiredFields = [
        'host',
        'creds'
    ];

    protected array $casts = [
        'creds' => ServerCreds::class
    ];

    protected function setupConfig(array $config)
    {
        parent::setupConfig($config);

        $this->serverSettings = new ServerSettings($config);
        $this->config = $config;
    }

    public function getConfig(): array
    {
        return array_merge(get_object_vars($this->serverSettings), $this->config, $this->creds->toArray());
    }
}
