<?php

namespace Valet;

class Architecture
{
    const ARM_BREW_PATH = '/opt/homebrew';
    const INTEL_BREW_PATH = '/usr/local';

    const ARM_64 = 'arm64';

    /**
     * @var string|null
     */
    private $brewPath = null;

    /**
     * @return string
     */
    public function getBrewPath()
    {
        if ($this->brewPath === null) {
            $this->defineBrewPath();
        }

        return $this->brewPath;
    }

    /**
     * @return bool
     */
    public function isArm64()
    {
        if (strpos(ARCH_NAME, self::ARM_64) !== false) {
            info('ARM Mac detected');

            return true;
        }
        info('Intel Mac detected');

        return false;
    }

    /**
     * @return void
     */
    private function defineBrewPath()
    {
        $this->brewPath = $this->isArm64() ?
            Architecture::ARM_BREW_PATH :
            Architecture::INTEL_BREW_PATH;
    }
}