<?php

declare(strict_types=1);

namespace PhpMyAdmin\Stores;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Helpers\Notifier;
use PhpMyAdmin\Model\Server;

class ServerStore extends Store
{
    protected ?int $currentServerIndex = null;

    protected ?Server $currentServer = null;

    /**
     * @var array<Server>
     */
    protected array $servers = [];

    public function __construct()
    {
        $this->setDefaults();
    }

    public function findServerByIndex(?int $index): ?Server
    {
        if (empty($this->servers) || is_null($index)) {
            return null;
        }

        $index > 0 && $index--;

        return $this->servers[$index] ?? null;
    }

    public function findServerIndexByHost(string $host): ?int
    {
        foreach ($this->servers as $index => $server) {
            if ($server->host === $host) {
                return ++$index;
            }
        }

        return null;
    }

    public function setCurrentServerIndex(?int $index): ?bool
    {
        if (is_null($index)) {
            $server = null;
        } else {
            $server = $this->findServerByIndex($index);

            if (empty($server)) {
                Notifier::error("Missing server index: $index");
            }
        }

        $this->currentServer = $server;
        $this->currentServerIndex = $index;

        if (is_null($index)) {
            return null;
        }

        return self::redeclareDbiConnect(self::updateCurrentServerGlobalData());
    }

    public function resetServersConfig(array $serversConfig)
    {
        $this->setDefaults();

        foreach ($serversConfig as $serverConfig) {
            $this->addServerConfig($serverConfig);
        }
    }

    public function setDefaults()
    {
        $this->servers = [];
        $this->setCurrentServerIndex(null);
    }

    public function getFirstIndex(): ?int
    {
        return $this->servers ? array_key_first($this->servers) + 1 : null;
    }

    public function addServerConfig(array $serverConfig)
    {
        $this->servers[] = new Server($serverConfig);

        if (empty($this->currentServerIndex)) {
            $this->setCurrentServerIndex($this->getFirstIndex());
        }
    }

    public static function fromServersConfig(array $serversConfig)
    {
        $store = self::instance();
        $store->resetServersConfig($serversConfig);
        return $store;
    }

    public static function currentServer(): ?Server
    {
        return self::instance()->currentServer;
    }

    public static function currentServerIndex(): ?int
    {
        return self::instance()->currentServerIndex;
    }

    public static function currentServerConfig(bool $isSimple = false): array
    {
        $server = self::currentServer();
        return self::getServerConfig($server, $isSimple);
    }

    public static function allServersConfig(): array
    {
        $instance = self::instance();
        return array_map(function (Server $server) {
            return self::getServerConfig($server);
        }, $instance->servers);
    }

    public static function allServers(): array
    {
        $servers = self::allServersConfig();
        return array_combine(range(1, count($servers)), array_values($servers));
    }

    protected static function getServerConfig(?Server $server, bool $isSimple = false)
    {
        $data = $isSimple ? [] : self::globalServerConfig();

        if ($server) {
            $config = array_merge($data, $server->getConfig());

            foreach ($data as $k => $v) {
                if (empty($config[$k]) && ($v || $v === 0)) {
                    $config[$k] = $v;
                }
            }

            $data = $config;
        }

        return $data;
    }

    public static function globalServerConfig(): array
    {
        $server = $GLOBALS['cfg']['Server'] ?? [];

        $server['port'] = empty($server['port']) ? 0 : (int)$server['port'];

        if (empty($server['socket'])) {
            $server['socket'] = null;
        }

        if (empty($server['host'])) {
            $server['host'] = 'localhost';
        }

        if (!isset($server['ssl'])) {
            $server['ssl'] = false;
        }

        if (!isset($server['compress'])) {
            $server['compress'] = false;
        }

        if (!isset($server['hide_connection_errors'])) {
            $server['hide_connection_errors'] = false;
        }

        return $server;
    }

    public static function updateCurrentServerGlobalData(): array
    {
        global $cfg;

        $config = self::currentServerConfig();
        $cfg['Server'] = $config;

        return $config;
    }

    public static function getCurrentServerConnectionParams(int $mode, ?array $server = null): array
    {
        self::updateCurrentServerGlobalData();
        $serverConfig = self::currentServerConfig();

        if ($mode == DatabaseInterface::CONNECT_USER) {
            $server = $serverConfig;
            return [
                $serverConfig['user'],
                $serverConfig['password'],
                $server
            ];
        }

        if ($mode == DatabaseInterface::CONNECT_CONTROL) {
            $server = [];

            $server['hide_connection_errors'] = $serverConfig['hide_connection_errors'];

            if (empty($serverConfig['controlhost'])) {
                $server['host'] = $serverConfig['host'];
                if ($serverConfig['port'] ?? null) {
                    $server['port'] = $serverConfig['port'];
                }
            } else {
                $server['host'] = $serverConfig['controlhost'];
                if ($serverConfig['controlport'] ?? null) {
                    $server['port'] = $serverConfig['controlport'];
                }
            }

            if ($server['host'] == $serverConfig['host']) {
                $shared = [
                    'socket',
                    'compress',
                    'ssl',
                    'ssl_key',
                    'ssl_cert',
                    'ssl_ca',
                    'ssl_ca_path',
                    'ssl_ciphers',
                    'ssl_verify',
                ];

                foreach ($shared as $item) {
                    if (!isset($serverConfig[$item])) {
                        continue;
                    }

                    $server[$item] = $serverConfig[$item];
                }
            }

            foreach ($serverConfig as $key => $val) {
                if (substr($key, 0, 8) !== 'control_') {
                    continue;
                }

                $server[substr($key, 8)] = $val;
            }

            return [
                $serverConfig['controluser'],
                $serverConfig['controlpass'],
                $server
            ];
        }

        if ($server === null) {
            return [
                null,
                null,
                null,
            ];
        }

        return [
            $server['user'] ?? null,
            $server['password'] ?? null,
            $server,
        ];
    }

    public static function setCurrentServerByDefault(): void
    {
        foreach (array_keys(self::allServers()) as $index) {
            if (self::setCurrentServerByIndex($index)) {
                break;
            }
        }
    }

    public static function setCurrentServerByIndex(int $index): ?bool
    {
        $instance = self::instance();
        return $instance->setCurrentServerIndex($index);
    }

    public static function setCurrentServerByHost(string $host): ?bool
    {
        $instance = self::instance();
        $index = $instance->findServerIndexByHost($host);

        if (empty($index)) {
            Notifier::error("Missing server host: $host");
        }

        return $instance->setCurrentServerIndex($index);
    }

    private static function redeclareDbiConnect($config): ?bool
    {
        global $dbi;

        if (empty($dbi)) {
            return null;
        }

        $connect = $dbi->connect(DatabaseInterface::CONNECT_USER, $config);

        return !!$connect;
//        empty($connect) && self::unsetCurrentServer();
    }

//    private static function unsetCurrentServer(): void
//    {
//        $instance = self::instance();
//        $index = $instance->currentServerIndex();
//        !is_null($index) && $instance->unsetServer($index);
//    }
//
//    public function unsetServer(int $index)
//    {
//        if (isset($this->servers[$index])) {
//            unset($this->servers[$index]);
//        }
//
//        if ($index === $this->currentServerIndex) {
//            $this->currentServerIndex = $this->getFirstIndex();
//        }
//    }
}
