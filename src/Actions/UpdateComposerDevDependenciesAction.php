<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Data\InstallSelections;
use SimaoCurado\Axiom\Enums\DebugToolPreset;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class UpdateComposerDevDependenciesAction
{
    public function __construct(private Filesystem $files) {}

    public function handle(InstallContext $context): void
    {
        if ($this->dependencies($context->selections) === []) {
            return;
        }

        $composerPath = $context->basePath.'/composer.json';

        if (! $this->files->exists($composerPath)) {
            $context->recordSkipped('composer.json');

            return;
        }

        /** @var array<string, mixed>|null $composer */
        $composer = json_decode((string) $this->files->get($composerPath), true);

        if (! is_array($composer)) {
            $context->recordSkipped('composer.json');

            return;
        }

        $composer['require-dev'] ??= [];

        if (! is_array($composer['require-dev'])) {
            $context->recordSkipped('composer.json');

            return;
        }

        $dependencies = $this->dependencies($context->selections, $composer);
        $hasChanges = false;

        foreach ($dependencies as $name => $version) {
            if (array_key_exists($name, $composer['require-dev']) && ! $context->selections->overwriteFiles) {
                continue;
            }

            if (! array_key_exists($name, $composer['require-dev']) || $composer['require-dev'][$name] !== $version) {
                $composer['require-dev'][$name] = $version;
                $hasChanges = true;
            }
        }

        if (! $hasChanges) {
            $context->recordSkipped('composer.json');

            return;
        }

        ksort($composer['require-dev']);

        $this->files->put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );

        $context->recordWritten('composer.json');
    }

    /**
     * @param  array<string, mixed>  $composer
     * @return array<string, string>
     */
    public function dependencies(InstallSelections $selections, array $composer = []): array
    {
        $dependencies = [];
        $installPhpStan = $selections->installPhpQualityDependencies || $selections->installPhpStan;
        $installRector = $selections->installPhpQualityDependencies || $selections->installRector;
        $installPint = $selections->installPhpQualityDependencies || $selections->installPint;
        $installTypeCoverage = $selections->installPhpQualityDependencies || $selections->installTypeCoverage;

        if ($installPhpStan) {
            $dependencies['larastan/larastan'] = '^3.9.3';
            $dependencies['phpstan/phpstan'] = '^2.1.45';
        }

        if ($installRector) {
            $dependencies['driftingly/rector-laravel'] = '^2.1.12';
            $dependencies['rector/rector'] = '^2.3.6';
        }

        if ($installPint) {
            $dependencies['laravel/pint'] = '^1.29.0';
        }

        if ($installTypeCoverage && ! $this->composerRequiresPackage($composer, 'laravel/pao')) {
            $dependencies['pestphp/pest-plugin-type-coverage'] = '^4.0.3';
        }

        if ($selections->debugTool === DebugToolPreset::Debugbar) {
            $dependencies['barryvdh/laravel-debugbar'] = '^4.2.6';
        }

        if ($selections->debugTool === DebugToolPreset::Telescope) {
            $dependencies['laravel/telescope'] = '^5.0';
        }

        ksort($dependencies);

        return $dependencies;
    }

    /**
     * @param  array<string, mixed>  $composer
     */
    private function composerRequiresPackage(array $composer, string $package): bool
    {
        $require = $composer['require'] ?? [];
        $requireDev = $composer['require-dev'] ?? [];

        return (is_array($require) && array_key_exists($package, $require))
            || (is_array($requireDev) && array_key_exists($package, $requireDev));
    }
}
