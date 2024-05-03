<?php

namespace Valet;

use DomainException;

class Opensearch
{
    const NGINX_CONFIGURATION_STUB = __DIR__ . '/../stubs/opensearch.conf';
    const NGINX_CONFIGURATION_PATH = '/etc/nginx/valet/opensearch.conf';

    const ES_CONFIG_YAML = '/etc/opensearch/opensearch.yml';
    const ES_CONFIG_DATA_PATH = 'path.data';
    const ES_CONFIG_DATA_BASEPATH = '/var/lib/';

    const ES_FORMULA_PREFIX = 'isaaceindhoven/opensearch-maintenance/';
    const ES_FORMULA_NAME = 'opensearch';
    const OPENSEARCH_V1_VERSION = '1';
    const OPENSEARCH_V2_VERSION = '2';

    const SUPPORTED_OS_FORMULAE = [
        self::OPENSEARCH_V1_VERSION => self::ES_FORMULA_NAME . '@' . self::OPENSEARCH_V1_VERSION,
        self::OPENSEARCH_V2_VERSION => self::ES_FORMULA_NAME,
    ];

    const OPENSEARCH_MAINTENANCE_TAP = 'nntoan/opensearch-maintenance';

    // Plugins.
    public const ANALYSIS_PHONETIC_PLUGIN = 'analysis-phonetic';
    public const ANALYSIS_ICU_EXTENSION = 'analysis-icu';

    const OPENSEARCH_PLUGINS = [
        self::ANALYSIS_PHONETIC_PLUGIN => [
            'default' => true,
        ],
        self::ANALYSIS_ICU_EXTENSION => [
            'default' => true,
        ],
    ];

    /**
     * @var string[]
     */
    protected $versions;

    public Brew $brew;
    public CommandLine $cli;
    public Filesystem $files;
    public Configuration $configuration;
    public Site $site;
    public PhpFpm $phpFpm;

    /**
     * Elasticsearch constructor.
     *
     * @param  Brew  $brew
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @param  Configuration  $configuration
     * @param  Site  $site
     * @param  PhpFpm  $phpFpm
     */
    public function __construct(
        Brew $brew,
        CommandLine $cli,
        Filesystem $files,
        Configuration $configuration,
        Site $site,
        PhpFpm $phpFpm
    ) {
        $this->cli = $cli;
        $this->brew = $brew;
        $this->site = $site;
        $this->files = $files;
        $this->configuration = $configuration;
        $this->phpFpm = $phpFpm;
    }

    /**
     * Install the service.
     *
     * @param  string  $version
     *
     * @return void
     */
    public function install($version = null)
    {
        $versions = $this->getVersions();
        $version = ($version ? $version : $this->getLatestVersion());

        if (!$this->isSupportedVersion($version)) {
            warning('The OpenSearch version you\'re installing is not supported.');
            warning('Available versions are: ' . implode(', ', array_keys($this->getVersions())));

            return;
        }

        if ($this->installed($version)) {
            info('[' . $versions[$version] . '] already installed');

            return;
        }

        // Tap
        if (!$this->brew->hasTap(self::OPENSEARCH_MAINTENANCE_TAP)) {
            info("[BREW TAP] Installing " . self::OPENSEARCH_MAINTENANCE_TAP);
            $this->brew->tap(self::OPENSEARCH_MAINTENANCE_TAP);
        } else {
            info("[BREW TAP] " . self::OPENSEARCH_MAINTENANCE_TAP . " already installed");
        }

        // Install dependencies
        // $this->cli->quietlyAsUser('brew install openjdk@17');
        // $this->brew->installOrFail('libyaml');
        // Install opensearch
        $this->brew->installOrFail($versions[$version]);
        // Restart just to make sure
        $this->restart($version);
    }

    /**
     * Returns wether Elasticsearch is installed.
     *
     * @param  string  $version
     *
     * @return bool
     */
    public function installed($version = null)
    {
        // todo; if we have let's say version 5.6 installed the check can give a false-positive
        //  return when current version (7.10) in Brew has the same formula now as 5.6 at the time.

        $versions = $this->getVersions();
        $majors = ($version ? [$version] : array_keys($versions));
        foreach ($majors as $version) {
            if ($this->brew->installed($versions[$version])) {
                return $version;
            }
        }

        return false;
    }

    /**
     * Restart the service.
     *
     * @param  string  $version
     *
     * @return void
     */
    public function restart($version = null)
    {
        $version = ($version ? $version : $this->getCurrentVersion());

        if (!$version) {
            // Fallback to highest major.
            $version = $this->installed();
        }

        if (!$this->installed($version)) {
            return;
        }

        $versions = $this->getVersions();
        info('[' . $versions[$version] . '] Restarting');
        $this->cli->quietlyAsUser('brew services restart ' . $versions[$version]);
    }

    /**
     * Stop all OpenSearch service.
     *
     * @param  string  $version
     *
     * @return void
     */
    public function stop($version = null)
    {
        $version = ($version ? $version : $this->getCurrentVersion());
        if (!$version) {
            return;
        }

        if (!$this->installed($version)) {
            return;
        }

        $versions = $this->getVersions();
        info('[' . $versions[$version] . '] Stopping');
        $this->cli->quietlyAsUser('brew services stop ' . $versions[$version]);
    }

    /**
     * Prepare for uninstallation.
     *
     * @return void
     */
    public function uninstall()
    {
        $this->stop();
        // todo; should do a 'brew remove <formula>' and 'rm -rf <stuff>'?
    }

    /**
     * @param $domain
     */
    public function updateDomain($domain)
    {
        $this->files->putAsUser(
            BREW_PREFIX . self::NGINX_CONFIGURATION_PATH,
            str_replace(
                ['VALET_DOMAIN'],
                [$domain],
                $this->files->get(self::NGINX_CONFIGURATION_STUB)
            )
        );
    }

    public function enforcePlugins($version, $onlyDefaults = true)
    {
        info("[PLUGINS] Enforcing plugins for opensearch@" . $version);
        $pluginBinary = BREW_PREFIX . '/bin/' . self::ES_FORMULA_NAME . '-plugin ';
        foreach (self::OPENSEARCH_PLUGINS as $plugin => $settings) {
            if ($onlyDefaults && $settings['default'] !== true) {
                continue;
            }

            $pluginPath = BREW_PREFIX . '/var/' . self::ES_FORMULA_NAME . '/plugins/' . $plugin;
            $resolvedPluginFiles = $this->files->scandir($pluginPath);
            foreach ($resolvedPluginFiles as $file) {
                if (strpos($file, $plugin) !== false && strpos($file, $version) === false) {
                    $this->cli->quietlyAsUser($pluginBinary . 'remove ' . $plugin);
                    $this->cli->quietlyAsUser($pluginBinary . 'install ' . $plugin);
                }
            }
        }
    }

    /**
     * Switch between versions of installed OpenSearch. Switch to the provided version.
     *
     * @param $version
     */
    public function switchTo($version)
    {
        $currentVersion = $this->getCurrentVersion();
        if (!$this->isSupportedVersion($version)) {
            throw new DomainException(
                "This version of OpenSearch is not supported. The following versions are supported: " . implode(
                    ', ',
                    array_keys($this->getVersions())
                ) . ($currentVersion ? "\nCurrent version is " . $currentVersion : "")
            );
        }

        // If the requested version equals that of the current running version, do not switch.
        if ($version === $currentVersion) {
            info('Already on this version');

            return;
        }

        // Make sure the requested version is installed.
        $versions = $this->getVersions();
        $installed = $this->installed($version);
        if (!$installed) {
            $this->brew->ensureInstalled($versions[$version]);
        }

        if ($currentVersion) {
            // Stop current version.
            $this->stop($currentVersion);
        }

        // Alter OpenSearch data path in config yaml.
        // OpenSearch stores the indices on disk. In this yaml the path to those indices is configured.
        // The indices are not compatible accross different OpenSearch versions. So, we configure a data
        // path for each OpenSearch version to keep them stored and thus prevent having to index after
        // switching or even break OpenSearch (it can't start properly with indices from another version).
        // todo; hmmm maybe we should do this also when installing?
        if (extension_loaded('yaml')) {
            $config = yaml_parse_file(BREW_PREFIX . self::ES_CONFIG_YAML);
            $openSearchBasePath = BREW_PREFIX . self::ES_CONFIG_DATA_BASEPATH;
            $config[self::ES_CONFIG_DATA_PATH] = $openSearchBasePath . self::ES_FORMULA_NAME . '@' . $version . '/';
            yaml_emit_file(BREW_PREFIX . self::ES_CONFIG_YAML, $config);
        } else {
            // Install PHP dependencies through installation of PHP.
            $this->phpFpm->install();
            warning("Switching OpenSearch requires YAML extension. Try switching again.");

            return;
        }

        // Unlink the current OpenSearch version.
        if (!$this->unlinkOS($currentVersion)) {
            return;
        }

        // Link the requested version.
        if (!$this->linkOS($version, $currentVersion)) {
            return;
        }

        // Enforcing default plugins for OpenSearch.
        $this->enforcePlugins($version);

        // Start requested version.
        $this->restart($version);

        info("Valet is now using [" . $versions[$version] . "]. You might need to reindex your data.");
    }

    /**
     * Get the formula name for a OpenSearch version.
     *
     * @param $version
     *
     * @return string Formula name
     */
    public function getFormulaName($version)
    {
        return self::SUPPORTED_OS_FORMULAE[$version];
    }

    /**
     * Returns the current running major version.
     *
     * @return bool|int|string
     */
    public function getCurrentVersion()
    {

        $currentVersion = false;
        $versions = $this->getVersions();

        foreach ($versions as $major => $formula) {
            if ($this->brew->isStartedService($formula)) {
                $currentVersion = $major;
            }
        }

        if ($currentVersion === false) {
            $osPath = BREW_PREFIX . '/bin/opensearch';
            if (!$this->files->isLink($osPath)) {
                throw new DomainException("Unable to determine linked OpenSearch.");
            }

            $resolvedPath = $this->files->readLink($osPath);
            $versions = self::SUPPORTED_OS_FORMULAE;
            foreach ($versions as $version => $brewname) {
                if (strpos($resolvedPath, '/opensearch@' . $version . '/') !== false ||
                    strpos($resolvedPath, '/opensearch/' . $version . '') !== false) {
                    $currentVersion = $version;
                }
            }
        }

        return $currentVersion;
    }

    /**
     * Returns array with available formulae in Brew and their stable and major version.
     *
     * @return array
     */
    public function getVersions()
    {
        if ($this->versions === null) {
            $this->versions = self::SUPPORTED_OS_FORMULAE;
        }

        return $this->versions;
    }

    /**
     * Returns the major of the latest version.
     */
    public function getLatestVersion()
    {
        return max(array_keys(self::SUPPORTED_OS_FORMULAE));
    }

    /**
     * Returns wether the version is supported in Brew.
     *
     * @param $version
     *
     * @return bool
     */
    public function isSupportedVersion($version)
    {
        return in_array($version, array_keys(self::SUPPORTED_OS_FORMULAE));
    }

    /**
     * Link a PHP version to be used as binary.
     *
     * @param $version
     * @param $currentVersion
     *
     * @return bool
     */
    private function linkOS($version, $currentVersion = null)
    {
        $isLinked = true;
        info("[opensearch@$version] Linking");
        $output = $this->cli->runAsUser(
            'brew link ' . self::SUPPORTED_OS_FORMULAE[$version] . ' --force --overwrite',
            function () use (&$isLinked) {
                $isLinked = false;
            }
        );

        // The output is about how many symlinks were created.
        // Sanitize the second half to prevent users from being confused.
        // So the only output would be:
        // Linking /usr/local/Cellar/valet-php@7.3/7.3.8... 25 symlinks created
        // Without the directions to create exports pointing towards the binaries.
        if (strpos($output, 'symlinks created')) {
            $output = substr($output, 0, strpos($output, 'symlinks created') + 8);
        }
        output($output);

        if ($isLinked === false) {
            warning(
                "Could not link OpenSearch version!" . PHP_EOL .
                "There appears to be an issue with your OpenSearch $version installation!" . PHP_EOL .
                "See the output above for more information." . PHP_EOL
            );
        }

        if ($currentVersion !== null && $isLinked === false) {
            info("Linking back to previous version to prevent broken installation!");
            $this->linkOS($currentVersion);
        }

        return $isLinked;
    }

    /**
     * Unlink a OpenSearch version, removing the binary symlink.
     *
     * @param $version
     *
     * @return bool
     */
    private function unlinkOS($version)
    {
        $isUnlinked = true;
        info("[opensearch@$version] Unlinking");
        output(
            $this->cli->runAsUser(
                'brew unlink ' . self::SUPPORTED_OS_FORMULAE[$version],
                function () use (&$isUnlinked) {
                    $isUnlinked = false;
                }
            )
        );
        if ($isUnlinked === false) {
            warning(
                "Could not unlink OpenSearch version!" . PHP_EOL .
                "There appears to be an issue with your OpenSearch $version installation!" . PHP_EOL .
                "See the output above for more information."
            );
        }

        return $isUnlinked;
    }
}
