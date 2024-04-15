<?php

declare(strict_types=1);

namespace Lotus\ValetProMax;

use DomainException;
use Lotus\ValetProMax\Extended\Site;
use Valet\Brew;
use Valet\CommandLine;
use Valet\Filesystem;

use function Valet\info;

class Elasticsearch extends AbstractDockerService
{
    /** @var string */
    protected const NGINX_CONFIGURATION_PATH = VALET_HOME_PATH . '/Nginx/elasticsearch.conf';

    /** @var string */
    protected const OPENSEARCH_CONFIG_YAML = '/etc/opensearch/opensearch.yml';
    /** @var string */
    protected const OPENSEARCH_CONFIG_DATA_PATH = 'path.data';
    /** @var string */
    protected const OPENSEARCH_CONFIG_DATA_BASEPATH = '/var/lib/%s/';
    /** @var string */
    protected const OPENSEARCH_PLUGIN_BIN = '/bin/opensearch-plugin';
    protected const OPENSEARCH_PLUGIN_PATH = '/var/opensearch/plugins/%s';
    /** @var string */
    protected const ANALYSIS_PHONETIC_PLUGIN = 'analysis-phonetic';
    /** @var string */
    protected const ANALYSIS_ICU_EXTENSION = 'analysis-icu';

    /** @var string[] */
    protected const OPENSEARCH_PLUGINS = [
        self::ANALYSIS_PHONETIC_PLUGIN => [
            'default' => true
        ],
        self::ANALYSIS_ICU_EXTENSION => [
            'default' => true
        ]
    ];

    /** @var string */
    protected const ES_DEFAULT_VERSION = 'opensearch@1';
    /** @var string[] */
    protected const ES_SUPPORTED_VERSIONS = [
        'opensearch',
        'opensearch@1',
        'elasticsearch6',
        'elasticsearch7',
        'elasticsearch8'
    ];
    /** @var string[] */
    protected const ES_DOCKER_VERSIONS = [
        'elasticsearch6',
        'elasticsearch7',
        'elasticsearch8'
    ];
    /** @var string[] */
    protected const ES_EOL_VERSIONS = [
        'opensearch@1',
        'elasticsearch@6'
    ];
    /** @var string[] */
    protected const ES_MAPPING_VERSIONS = [
        'opensearch@1' => 'opensearch@1',
        'opensearch@2' => 'opensearch',
        'elasticsearch@6' => 'elasticsearch6',
        'elasticsearch@7' => 'elasticsearch7',
        'elasticsearch@8' => 'elasticsearch8'
    ];

    /** @var string[] */
    protected $taps = [
        'isaaceindhoven/opensearch-maintenance',
    ];

    /** @var Brew */
    protected $brew;
    /** @var Site */
    protected $site;

    /**
     * @param CommandLine $cli
     * @param Filesystem $files
     * @param Brew $brew
     * @param Site $site
     */
    public function __construct(
        CommandLine $cli,
        Filesystem $files,
        Brew $brew,
        Site $site
    ) {
        parent::__construct($cli, $files);

        $this->brew = $brew;
        $this->site = $site;
    }

    /**
     * Returns supported elasticsearch versions.
     *
     * @return string[]
     */
    public function getSupportedVersions()
    {
        return static::ES_SUPPORTED_VERSIONS;
    }

    /**
     * Returns mapped elasticsearch versions.
     *
     * @return string[]
     */
    public function getMappedVersions()
    {
        return static::ES_MAPPING_VERSIONS;
    }

    /**
     * Returns supported elasticsearch versions running in Docker.
     *
     * @return string[]
     */
    public function getDockerVersions()
    {
        return static::ES_DOCKER_VERSIONS;
    }

    /**
     * Returns end-of-life elasticsearch versions.
     *
     * @return string[]
     */
    public function getEolVersions()
    {
        return static::ES_EOL_VERSIONS;
    }

    /**
     * Returns if provided version is supported.
     *
     * @param $version
     *
     * @return bool
     */
    public function isSupportedVersion($version): bool
    {
        return in_array($version, $this->getSupportedVersions());
    }

    /**
     * Returns is provided version is running as Docker container. If not, it's running natively (installed with Brew).
     *
     * @param $version
     *
     * @return bool
     */
    public function isDockerVersion($version): bool
    {
        return in_array($version, $this->getDockerVersions());
    }

    /**
     * Returns running elasticsearch version.
     *
     * @return string|null
     */
    public function getCurrentVersion(): ?string
    {
        $runningServices = $this->brew->getAllRunningServices()
            ->merge($this->getAllRunningContainers())
            ->filter(function ($service) {
                return $this->isSupportedVersion($service);
            });

        return $runningServices->first();
    }

    /**
     * Installs the requested version and switches to it.
     *
     * @param string $version
     * @param string $tld
     */
    public function useVersion($version = self::ES_DEFAULT_VERSION, $tld = 'test')
    {
        $version = $this->normalizeEsVersion($version);

        if (!$this->isSupportedVersion($version)) {
            throw new DomainException(
                sprintf(
                    'Invalid Elasticsearch version given. Available versions: %s',
                    implode(', ', static::ES_SUPPORTED_VERSIONS)
                )
            );
        }

        $currentVersion = $this->getCurrentVersion();
        // If the requested version equals that of the current running version, do not switch.
        if ($version === $currentVersion) {
            info('Already on this version');

            return;
        }
        if ($currentVersion) {
            // Stop current version.
            $this->stop($currentVersion);
        }

        $this->install($version, $tld);
    }


    /**
     * Stop elasticsearch.
     *
     * @param string|null $version
     */
    public function stop($version = null)
    {
        $version = ($version ? $version : $this->getCurrentVersion());
        if (!$version) {
            return;
        }

        if ($this->isDockerVersion($version)) {
            $this->stopContainer($version);
            return;
        }

        if (!$this->brew->installed($version)) {
            return;
        }

        $this->brew->stopService($version);
        $this->cli->quietlyAsUser('brew services stop ' . $version);
    }

    /**
     * Restart elasticsearch.
     *
     * @param string|null $version
     */
    public function restart($version = null)
    {
        $version = ($version ? $version : $this->getCurrentVersion());
        if (!$version) {
            return;
        }

        if ($this->isDockerVersion($version)) {
            $this->stopContainer($version);
            $this->upContainer($version);
            return;
        }

        if (!$this->brew->installed($version)) {
            return;
        }

        info("Restarting {$version}...");
        $this->cli->quietlyAsUser('brew services restart ' . $version);
    }

    /**
     * Install the requested version of elasticsearch.
     *
     * @param string $version
     * @param string $tld
     */
    public function install($version = self::ES_DEFAULT_VERSION, $tld = 'test')
    {
        $version = $this->normalizeEsVersion($version);

        if (!$this->isSupportedVersion($version)) {
            throw new DomainException(
                sprintf(
                    'Invalid Elasticsearch version given. Available versions: %s',
                    implode(', ', static::ES_SUPPORTED_VERSIONS)
                )
            );
        }

        if (!$this->isDockerVersion($version)) {
            // For Docker versions we don't need to anything here.

            // todo; install java dependency? and remove other java deps? seems like there can be only one running.
            // opensearch requires openjdk (installed automatically)
            // elasticsearch@6 requires openjdk@17 (installed automatically)
            //      seems like there can be only one openjdk when installing. after installing it doesn't matter.
            //      if this dependency is installed we need to launch es with this java version,
            //      see https://github.com/Homebrew/homebrew-core/issues/100260

            $this->brew->ensureInstalled($version, [], $this->taps);

            if (extension_loaded('yaml')) {
                $config = yaml_parse_file(BREW_PREFIX . static::OPENSEARCH_CONFIG_YAML);
                $openSearchBasePath = BREW_PREFIX . static::OPENSEARCH_CONFIG_DATA_BASEPATH;
                $config[self::OPENSEARCH_CONFIG_DATA_PATH] = sprintf($openSearchBasePath, $version);
                yaml_emit_file(BREW_PREFIX . static::OPENSEARCH_CONFIG_YAML, $config);
            } else {
                throw new DomainException("Switching OpenSearch requires YAML extension. Please run `valet-pro install` then try again.");
            }

            // ==> opensearch
            //Data:    /usr/local/var/lib/opensearch/
            //Logs:    /usr/local/var/log/opensearch/*.log
            //Plugins: /usr/local/var/opensearch/plugins/
            //Config:  /usr/local/etc/opensearch/
            // ==> elasticsearch@6
            //Data:    /usr/local/var/lib/elasticsearch/
            //Logs:    /usr/local/var/log/elasticsearch/*.log
            //Plugins: /usr/local/var/elasticsearch/plugins/
            //Config:  /usr/local/etc/elasticsearch/

            $this->enforcePlugins($version);
        }

        $this->restart($version);
        $this->site->proxyCreate('elasticsearch', 'http://127.0.0.1:9200', true);
    }

    /**
     * Uninstall all supported versions.
     */
    public function uninstall()
    {
        $this->site->proxyDelete('elasticsearch');

        // Remove nginx domain listen file.
        $this->files->unlink(static::NGINX_CONFIGURATION_PATH);

        $versions = array_merge($this->getSupportedVersions(), $this->getEolVersions());
        foreach ($versions as $version) {
            $this->stop($version);
            if ($this->isDockerVersion($version)) {
                $this->downContainer($version);
            } else {
                $this->brew->uninstallFormula($version);
            }
        }

        // Legacy elasticsearch files
        if (file_exists(BREW_PREFIX . '/var/elasticsearch')) {
            $this->files->rmDirAndContents(BREW_PREFIX . '/var/elasticsearch');
        }
        $this->files->unlink(BREW_PREFIX . '/var/log/elasticsearch.log');
        if (file_exists(BREW_PREFIX . '/var/log/elasticsearch')) {
            $this->files->rmDirAndContents(BREW_PREFIX . '/var/log/elasticsearch');
        }
        if (file_exists(BREW_PREFIX . '/var/lib/elasticsearch')) {
            $this->files->rmDirAndContents(BREW_PREFIX . '/var/lib/elasticsearch');
        }
        if (file_exists(BREW_PREFIX . '/etc/elasticsearch')) {
            $this->files->rmDirAndContents(BREW_PREFIX . '/etc/elasticsearch');
        }

        // Opensearch files
        if (file_exists(BREW_PREFIX . '/var/opensearch')) {
            $this->files->rmDirAndContents(BREW_PREFIX . '/var/opensearch');
        }
        $this->files->unlink(BREW_PREFIX . '/var/log/opensearch.log');
        if (file_exists(BREW_PREFIX . '/var/log/opensearch')) {
            $this->files->rmDirAndContents(BREW_PREFIX . '/var/log/opensearch');
        }
        if (file_exists(BREW_PREFIX . '/var/lib/opensearch')) {
            $this->files->rmDirAndContents(BREW_PREFIX . '/var/lib/opensearch');
        }
        if (file_exists(BREW_PREFIX . '/var/lib/opensearch@1')) {
            $this->files->rmDirAndContents(BREW_PREFIX . '/var/lib/opensearch@1');
        }
        if (file_exists(BREW_PREFIX . '/etc/opensearch')) {
            $this->files->rmDirAndContents(BREW_PREFIX . '/etc/opensearch');
        }
    }

    /**
     * Enforces installation of default Elasticsearch plugins.
     *
     * @param string $version
     * @param bool $onlyDefaults
     *
     * @return void
     */
    public function enforcePlugins(string $version, bool $onlyDefaults = true): void
    {
        info("[OPENSEARCH-PLUGINS] Enforcing plugins for " . $version);
        $pluginBinary = BREW_PREFIX . static::OPENSEARCH_PLUGIN_BIN;
        $pluginBinaryActualPath = $this->files->readLink($pluginBinary);
        $currentVersion = preg_replace(
            '/\/([0-9]+)(?:.)([0-9]+)(?:.)([0-9+])\//i',
            '$1.$2.$3',
            $pluginBinaryActualPath
        );

        foreach (self::OPENSEARCH_PLUGINS as $plugin => $settings) {
            if ($onlyDefaults && $settings['default'] !== true) {
                continue;
            }

            $pluginPath = BREW_PREFIX . sprintf(static::OPENSEARCH_PLUGIN_PATH, $plugin);
            if (!$this->files->isDir($pluginPath)) {
                $this->cli->quietlyAsUser($pluginBinary . 'install ' . $plugin);
                continue;
            }
            $resolvedPluginFiles = $this->files->scandir($pluginPath);
            foreach ($resolvedPluginFiles as $file) {
                if (str_contains($file, $plugin) && !str_contains($file, $currentVersion)) {
                    $this->cli->quietlyAsUser($pluginBinary . ' remove ' . $plugin);
                    $this->cli->quietlyAsUser($pluginBinary . ' install ' . $plugin);
                }
            }
        }
    }

    /**
     * If passed elasticsearch@7, or elasticsearch7, or 7 formats, normalize to elasticsearch@7 format.
     */
    public function normalizeEsVersion(?string $version): string
    {
        $versionNumber = preg_replace('/(?:(elasticsearch|opensearch)@?)?([0-9+])/i', '$2', (string)$version);

        $normaliseVersion = match ($versionNumber) {
            '1', '2' => 'opensearch@' . $versionNumber,
            '6', '7', '8' => 'elasticsearch@' . $versionNumber
        };

        return static::ES_MAPPING_VERSIONS[$normaliseVersion];
    }
}
