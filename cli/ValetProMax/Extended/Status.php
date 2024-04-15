<?php

declare(strict_types=1);

namespace Lotus\ValetProMax\Extended;

use Lotus\ValetProMax\Mailhog;
use Lotus\ValetProMax\Mysql;
use Lotus\ValetProMax\Rabbitmq;
use Lotus\ValetProMax\RedisService;
use Lotus\ValetProMax\Varnish;
use Valet\Brew;
use Valet\CommandLine;
use Valet\Configuration;
use Valet\Filesystem;
use Valet\Status as ValetStatus;

class Status extends ValetStatus
{
    /** @var Mysql */
    protected $mysql;
    /** @var Mailhog */
    protected $mailhog;
    /** @var Varnish */
    protected $varnish;
    /** @var RedisService */
    protected $redis;
    /** @var Rabbitmq */
    protected $rabbitmq;

    /**
     * @param Configuration $config
     * @param Brew $brew
     * @param CommandLine $cli
     * @param Filesystem $files
     * @param Mysql $mysql
     * @param Mailhog $mailhog
     * @param Varnish $varnish
     * @param RedisService $redis
     * @param Rabbitmq $rabbitmq
     */
    public function __construct(
        Configuration $config,
        Brew $brew,
        CommandLine $cli,
        Filesystem $files,
        Mysql $mysql,
        Mailhog $mailhog,
        Varnish $varnish,
        RedisService $redis,
        Rabbitmq $rabbitmq
    ) {
        parent::__construct($config, $brew, $cli, $files);

        $this->mysql = $mysql;
        $this->mailhog = $mailhog;
        $this->varnish = $varnish;
        $this->redis = $redis;
        $this->rabbitmq = $rabbitmq;
    }

    /**
     * Returns list of Laravel Valet and Valet Pro Max checks.
     *
     * @return array
     */
    public function checks(): array
    {
        $checks = parent::checks();

        $mysqlVersion = $this->mysql->installedVersion();

        $checks[] = [
            'description' => '[Valet Pro Max] Is Mysql (' . $mysqlVersion . ') installed?',
            'check' => function () {
                return $this->mysql->installedVersion();
            },
            'debug' => 'Run `composer require nntoan/valet-pro-max` and `valet-pro install`.'
        ];
        $checks[] = [
            'description' => '[Valet Pro Max] Is Mailhog installed?',
            'check' => function () {
                return $this->mailhog->installed();
            },
            'debug' => 'Run `composer require nntoan/valet-pro-max` and `valet-pro install`.'
        ];

        if ($this->varnish->installed() || $this->varnish->isEnabled()) {
            $checks[] = [
                'description' => '[Valet Pro Max] Is Varnish installed?',
                'check' => function () {
                    return $this->varnish->installed() && $this->varnish->isEnabled();
                },
                'debug' => 'Varnish is installed but not enabled, you might run `valet-plus varnish on`.'
            ];
            //todo; actually test something?
        }
        if ($this->redis->installed() || $this->redis->isEnabled()) {
            $checks[] = [
                'description' => '[Valet Pro Max] Is Redis installed?',
                'check' => function () {
                    return $this->redis->installed() && $this->redis->isEnabled();
                },
                'debug' => 'Redis is installed but not enabled, you might run `valet-pro redis on`.'
            ];
            //todo; actually test something?
        }
        if ($this->rabbitmq->installed() || $this->rabbitmq->isEnabled()) {
            $checks[] = [
                'description' => '[Valet Pro Max] Is Rabbitmq installed?',
                'check' => function () {
                    return $this->rabbitmq->installed() && $this->rabbitmq->isEnabled();
                },
                'debug' => 'Rabbitmq is installed but not enabled, you might run `valet-pro rabbitmq on`.'
            ];
            //todo; actually test something?
        }

        return $checks;
    }
}
