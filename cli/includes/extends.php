<?php

/**
 * We use Illuminate's Container to create a singleton class for extended valet classes.
 *
 */

use Illuminate\Container\Container;
use Valet\CommandLine;

define('ARCH_NAME', (new CommandLine())->run('printf $(uname -m)'));

Container::getInstance()->singleton(
    \Valet\Valet::class,
    \Lotus\ValetProMax\Extended\Valet::class
);
Container::getInstance()->singleton(
    \Valet\Brew::class,
    \Lotus\ValetProMax\Extended\Brew::class
);
Container::getInstance()->singleton(
    \Valet\Configuration::class,
    \Lotus\ValetProMax\Extended\Configuration::class
);
Container::getInstance()->singleton(
    \Valet\Nginx::class,
    \Lotus\ValetProMax\Extended\Nginx::class
);
Container::getInstance()->singleton(
    \Valet\PhpFpm::class,
    \Lotus\ValetProMax\Extended\PhpFpm::class
);
Container::getInstance()->singleton(
    \Valet\Site::class,
    \Lotus\ValetProMax\Extended\Site::class
);
Container::getInstance()->singleton(
    \Valet\Status::class,
    \Lotus\ValetProMax\Extended\Status::class
);

/**
 * Determine if current machine is ARM64 machine or not.
 */
function is_arm64(): bool
{
    return str_contains(ARCH_NAME, 'arm64');
}
