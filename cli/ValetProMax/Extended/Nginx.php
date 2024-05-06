<?php

declare(strict_types=1);

namespace Lotus\ValetProMax\Extended;

use Valet\Nginx as ValetNginx;

class Nginx extends ValetNginx
{
    /**
     * @inheritdoc
     */
    public function installServer(): void
    {
        parent::installServer();
        $this->configureFastCgiParams();
    }

    /**
     * Merge fastcgi_params from Laravel Valet with our optimizations.
     *
     * @return void
     */
    public function configureFastCgiParams(): void
    {
        // Merge fastcgi_params from Laravel Valet with our optimizations.
        $contents = $this->files->get(BREW_PREFIX . '/etc/nginx/fastcgi_params');
        $contents .= $this->files->get(__DIR__ . '/../../stubs/nginx/fastcgi_params');

        $this->files->putAsUser(
            BREW_PREFIX . '/etc/nginx/fastcgi_params',
            str_replace(
                [
                    'fastcgi_buffer_size 512k;',
                    'fastcgi_buffers 16 512k;'
                ],
                [
                    '#fastcgi_buffer_size 512k;',
                    '#fastcgi_buffers 16 512k;'
                ],
                $contents
            )
        );
    }
}
