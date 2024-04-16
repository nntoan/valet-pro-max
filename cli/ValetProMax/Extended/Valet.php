<?php

declare(strict_types=1);

namespace Lotus\ValetProMax\Extended;

use GuzzleHttp\Client;
use Valet\Valet as ValetValet;

class Valet extends ValetValet
{
    public $valetProMaxBin = BREW_PREFIX . '/bin/valet-pro';

    /**
     * Symlink the Valet Bash script into the user's local bin.
     */
    public function symlinkToUsersBin(): void
    {
        parent::symlinkToUsersBin();
        $this->cli->runAsUser('ln -s "' . realpath(__DIR__ . '/../../valet-pro') . '" ' . $this->valetProMaxBin);
    }

    /**
     * Remove the symlink from the user's local bin.
     */
    public function unlinkFromUsersBin(): void
    {
        parent::unlinkFromUsersBin();
        $this->cli->quietlyAsUser('rm ' . $this->valetProMaxBin);
    }

    /**
     * Create the "sudoers.d" entry for running Valet.
     */
    public function createSudoersEntry(): void
    {
        parent::createSudoersEntry();
        $this->files->put('/etc/sudoers.d/valet-pro', 'Cmnd_Alias VALET_PRO = ' . BREW_PREFIX . '/bin/valet-pro *
%admin ALL=(root) NOPASSWD:SETENV: VALET_PRO' . PHP_EOL);
    }

    /**
     * Remove the "sudoers.d" entry for running Valet.
     */
    public function removeSudoersEntry(): void
    {
        parent::removeSudoersEntry();
        $this->cli->quietly('rm /etc/sudoers.d/valet-pro');
    }

    /**
     * Determine if this is the latest version of Valet Pro Max.
     *
     * @param string $currentVersion
     *
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function onLatestProMaxVersion(string $currentVersion): bool
    {
        $url = 'https://api.github.com/repos/nntoan/valet-pro-max/releases/latest';
        $response = json_decode((string)(new Client())->get($url)->getBody());

        return version_compare($currentVersion, trim($response->tag_name, 'v'), '>=');
    }
}
