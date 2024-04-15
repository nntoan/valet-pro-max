<?php

declare(strict_types=1);

namespace Lotus\ValetProMax;

class Architecture
{
    /**
     * Detect if machine is running on a ARM64 architecture.
     *
     * @return bool
     */
    public function isArm64(): bool
    {
        return str_contains(ARCH_NAME, 'arm64');
    }
}