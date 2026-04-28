<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Data\InstallSelections;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class UpdatePackageDevDependenciesAction
{
    public function __construct(private Filesystem $files) {}

    public function handle(InstallContext $context): void
    {
        if ($this->dependencies($context->selections) === []) {
            return;
        }

        $packagePath = $context->basePath.'/package.json';

        if (! $this->files->exists($packagePath)) {
            $context->recordSkipped('package.json');

            return;
        }

        /** @var array<string, mixed>|null $package */
        $package = json_decode((string) $this->files->get($packagePath), true);

        if (! is_array($package)) {
            $context->recordSkipped('package.json');

            return;
        }

        $package['devDependencies'] ??= [];

        if (! is_array($package['devDependencies'])) {
            $context->recordSkipped('package.json');

            return;
        }

        $dependencies = $this->dependencies($context->selections);
        $hasChanges = false;

        foreach ($dependencies as $name => $version) {
            if (array_key_exists($name, $package['devDependencies']) && ! $context->selections->overwriteFiles) {
                continue;
            }

            if (! array_key_exists($name, $package['devDependencies']) || $package['devDependencies'][$name] !== $version) {
                $package['devDependencies'][$name] = $version;
                $hasChanges = true;
            }
        }

        if (! $hasChanges) {
            $context->recordSkipped('package.json');

            return;
        }

        ksort($package['devDependencies']);

        $this->files->put(
            $packagePath,
            json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );

        $context->recordWritten('package.json');
    }

    /**
     * @return array<string, string>
     */
    public function dependencies(InstallSelections $selections): array
    {
        $dependencies = [];
        $installFrontendBundle = $selections->installFrontendQualityDependencies;

        if ($installFrontendBundle || $selections->installConcurrently) {
            $dependencies['concurrently'] = '^9.2.1';
        }

        if ($installFrontendBundle || $selections->installNpmCheckUpdates) {
            $dependencies['npm-check-updates'] = '^19.3.2';
        }

        if ($installFrontendBundle || $selections->installOxlint) {
            $dependencies['oxlint'] = '^1.48.0';
        }

        if ($installFrontendBundle || $selections->installPrettier) {
            $dependencies['prettier'] = '^3.8.1';
            $dependencies['prettier-plugin-organize-imports'] = '^4.3.0';
            $dependencies['prettier-plugin-tailwindcss'] = '^0.7.2';
        }

        ksort($dependencies);

        return $dependencies;
    }
}
