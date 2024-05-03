<?php

namespace Valet;

use ValetDriver;

class DevTools
{
    const WP_CLI_TOOL = 'wp-cli';
    const PV_TOOL = 'pv';
    const GEOIP_TOOL = 'geoip';
    const ZLIB_TOOL = 'zlib';
    const JQ = 'jq';
    const LIBYAML = 'libyaml';

    const SUPPORTED_TOOLS = [
        self::WP_CLI_TOOL,
        self::PV_TOOL,
        self::GEOIP_TOOL,
        self::ZLIB_TOOL,
        self::JQ,
        self::LIBYAML,
    ];

    public Brew $brew;
    public CommandLine $cli;
    public Filesystem $files;
    public Configuration $configuration;
    public Site $site;
    public Mysql $mysql;

    /**
     * Create a new Nginx instance.
     *
     * @param  Brew  $brew
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     * @param  Configuration  $configuration
     * @param  Site  $site
     * @param  Mysql  $mysql
     */
    public function __construct(
        Brew $brew,
        CommandLine $cli,
        Filesystem $files,
        Configuration $configuration,
        Site $site,
        Mysql $mysql
    ) {
        $this->cli = $cli;
        $this->brew = $brew;
        $this->site = $site;
        $this->files = $files;
        $this->configuration = $configuration;
        $this->mysql = $mysql;
    }

    /**
     * Install development tools using brew.
     *
     * @return void
     */
    public function install()
    {
        info('[devtools] Installing tools');

        foreach (self::SUPPORTED_TOOLS as $tool) {
            if ($this->brew->installed($tool)) {
                output("\t" . $tool . ' already installed, skipping...');
            } else {
                $this->brew->ensureInstalled($tool, []);
            }
        }
    }

    /**
     * Uninstall development tools using brew.
     *
     * @return void
     */
    public function uninstall()
    {
        info('[devtools] Uninstalling tools');

        foreach (self::SUPPORTED_TOOLS as $tool) {
            if (!$this->brew->installed($tool)) {
                info('[devtools] ' . $tool . ' already uninstalled');
            } else {
                $this->brew->ensureUninstalled($tool, ['--force']);
            }
        }
    }

    public function sshkey()
    {
        $this->cli->passthru('pbcopy < ~/.ssh/id_rsa.pub');
        info('Copied ssh key to your clipboard');
    }

    public function configure()
    {
        require realpath(__DIR__ . '/../drivers/require.php');

        $driver = ValetDriver::assign(getcwd(), basename(getcwd()), '/');

        $secured = $this->site->secured();
        $domain = $this->site->host(getcwd()) . '.' . $this->configuration->read()['domain'];
        $isSecure = in_array($domain, $secured);
        $url = ($isSecure ? 'https://' : 'http://') . $domain;

        if (method_exists($driver, 'configure')) {
            return $driver->configure($this, $url);
        }

        info('No configuration settings found.');
    }
}
