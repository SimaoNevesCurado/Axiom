<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use Illuminate\Filesystem\Filesystem;

final readonly class DetectFortifyAction
{
    public function __construct(private Filesystem $files) {}

    public function handle(string $basePath): bool
    {
        $composerPath = $basePath.'/composer.json';

        if (! $this->files->exists($composerPath)) {
            return false;
        }

        /** @var array<string, mixed>|null $composer */
        $composer = json_decode((string) $this->files->get($composerPath), true);

        if (! is_array($composer) || ! isset($composer['require']) || ! is_array($composer['require'])) {
            return false;
        }

        return array_key_exists('laravel/fortify', $composer['require']);
    }
}
