<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Enums\FrontendStack;

final readonly class DetectFrontendStackAction
{
    public function __construct(private Filesystem $files) {}

    public function handle(string $basePath): FrontendStack
    {
        $packagePath = $basePath.'/package.json';

        if (! $this->files->exists($packagePath)) {
            return FrontendStack::None;
        }

        /** @var array<string, mixed>|null $package */
        $package = json_decode((string) $this->files->get($packagePath), true);

        if (! is_array($package)) {
            return FrontendStack::None;
        }

        $dependencies = [];

        if (isset($package['dependencies']) && is_array($package['dependencies'])) {
            $dependencies = array_merge($dependencies, array_keys($package['dependencies']));
        }

        if (isset($package['devDependencies']) && is_array($package['devDependencies'])) {
            $dependencies = array_merge($dependencies, array_keys($package['devDependencies']));
        }

        if (in_array('react', $dependencies, true) || in_array('@inertiajs/react', $dependencies, true)) {
            return FrontendStack::React;
        }

        if (in_array('vue', $dependencies, true) || in_array('@inertiajs/vue3', $dependencies, true)) {
            return FrontendStack::Vue;
        }

        return FrontendStack::None;
    }
}
