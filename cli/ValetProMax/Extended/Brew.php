<?php

declare(strict_types=1);

namespace Lotus\ValetProMax\Extended;

use Valet\Brew as ValetBrew;

class Brew extends ValetBrew
{
    /**
     * Check if brew has the given tap.
     *
     * @param string $formula
     *
     * @return bool
     */
    public function hasTap(string $formula): bool
    {
        return strpos($this->cli->runAsUser("brew tap | grep $formula"), $formula) !== false;
    }
}