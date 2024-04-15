<?php

declare(strict_types=1);

namespace Lotus\ValetProMax;

class Memcache extends AbstractPhpExtension
{
    /** @var string */
    protected const EXTENSION_NAME = PhpExtension::MEMCACHE_EXTENSION;
}
