<?php

declare(strict_types=1);

namespace Lotus\ValetProMax\Extended;

use GuzzleHttp\Client;
use Valet\Valet as ValetValet;

class Valet extends ValetValet
{
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
