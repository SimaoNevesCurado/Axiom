<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;

final readonly class ResolveStubPathAction
{
    public function __construct(private Filesystem $files) {}

    public function path(string $relativePath): string
    {
        return __DIR__.'/../../resources/stubs/'.$relativePath;
    }

    public function contents(string $relativePath): string
    {
        return (string) $this->files->get($this->path($relativePath));
    }

    /**
     * @param  array<string, string>  $replacements
     */
    public function render(string $relativePath, array $replacements): string
    {
        return strtr($this->contents($relativePath), $replacements);
    }
}
