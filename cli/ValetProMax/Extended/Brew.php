<?php

declare(strict_types=1);

namespace Lotus\ValetProMax\Extended;

use DomainException;
use Valet\Brew as ValetBrew;

use function Valet\user;

class Brew extends ValetBrew
{
    /**
     * Untap the given formulas.
     */
    public function untap($formulas): void
    {
        $formulas = is_array($formulas) ? $formulas : func_get_args();

        foreach ($formulas as $formula) {
            $this->cli->passthru(
                static::BREW_DISABLE_AUTO_CLEANUP . ' sudo -u "' . user() . '" brew untap ' . $formula
            );
        }
    }

    /**
     * Check if brew has the given tap.
     *
     * @param  string  $formula
     *
     * @return bool
     */
    public function hasTap(string $formula): bool
    {
        return str_contains($this->cli->runAsUser("brew tap | grep $formula"), $formula);
    }

    /**
     * Gets the currently linked formula by identifying the symlink in the homebrew bin directory.
     * Different to ->linkedOpenSearch() in that this will just get the linked directory name,
     * whether that is opensearch, opensearch1 or opensearch@1.
     */
    public function getLinkedOpenSearchFormula(): string
    {
        $matches = $this->getParsedLinkedOpenSearch();

        return $matches[1] . $matches[2];
    }

    /**
     * Get the linked php parsed.
     */
    public function getParsedLinkedOpenSearch(): array
    {
        if (!$this->hasLinkedOpenSearch()) {
            throw new DomainException('OpenSearch appears not to be linked. Please run [valet-pro use opensearch@X]');
        }

        $resolvedPath = $this->files->readLink(BREW_PREFIX . '/bin/opensearch');

        return $this->parseOpenSearchPath($resolvedPath);
    }

    /**
     * Determine if opensearch is currently linked.
     */
    public function hasLinkedOpenSearch(): bool
    {
        return $this->files->isLink(BREW_PREFIX . '/bin/opensearch');
    }

    /**
     * Parse homebrew Opensearch Path.
     */
    public function parseOpenSearchPath(string $resolvedPath): array
    {
        /**
         * Typical homebrew path resolutions are like:
         * "../Cellar/opensearch@1/1.3.9/bin/opensearch"
         * or older styles:
         * "../Cellar/opensearch/1.3.12_2/bin/opensearch
         * "../Cellar/opensearch1/bin/opensearch.
         */
        preg_match('~\w{3,}/(opensearch)(@?\d)?/(\d\.\d)?([_\d\.]*)?/?\w{3,}~', $resolvedPath, $matches);

        return $matches;
    }
}
