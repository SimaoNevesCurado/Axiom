<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Support;

use SimaoCurado\Axiom\Data\InstallResult;
use SimaoCurado\Axiom\Data\InstallSelections;

final class InstallContext
{
    /**
     * @var list<string>
     */
    public array $written = [];

    /**
     * @var list<string>
     */
    public array $skipped = [];

    public function __construct(
        public readonly InstallSelections $selections,
        public readonly string $basePath,
    ) {}

    public function relativePath(string $path): string
    {
        return ltrim(str_replace($this->basePath, '', $path), '/');
    }

    public function recordWritten(string $path): void
    {
        $this->appendUnique($this->written, $path);
    }

    public function recordSkipped(string $path): void
    {
        if (in_array($path, $this->written, true)) {
            return;
        }

        $this->appendUnique($this->skipped, $path);
    }

    public function result(): InstallResult
    {
        return new InstallResult($this->written, $this->skipped);
    }

    /**
     * @param  list<string>  $items
     */
    private function appendUnique(array &$items, string $value): void
    {
        if (! in_array($value, $items, true)) {
            $items[] = $value;
        }
    }
}
