<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Data\FrontendStackDetectionResult;
use SimaoCurado\Axiom\Enums\FrontendStack;

final readonly class DetectFrontendStackAction
{
    public function __construct(private Filesystem $files) {}

    public function handle(string $basePath): FrontendStackDetectionResult
    {
        $packagePath = $basePath.'/package.json';

        if (! $this->files->exists($packagePath)) {
            return new FrontendStackDetectionResult(
                stack: FrontendStack::Blade,
                reason: 'No package.json found',
            );
        }

        /** @var array<string, mixed>|null $package */
        $package = json_decode((string) $this->files->get($packagePath), true);

        if (! is_array($package)) {
            return new FrontendStackDetectionResult(
                stack: FrontendStack::Blade,
                reason: 'Invalid package.json',
            );
        }

        /** @var array<string, mixed> $dependencies */
        $dependencies = is_array($package['dependencies'] ?? null) ? $package['dependencies'] : [];
        /** @var array<string, mixed> $devDependencies */
        $devDependencies = is_array($package['devDependencies'] ?? null) ? $package['devDependencies'] : [];
        $combined = $dependencies + $devDependencies;

        if (array_key_exists('@inertiajs/vue3', $combined)) {
            return new FrontendStackDetectionResult(
                stack: FrontendStack::InertiaVue,
                reason: 'Detected @inertiajs/vue3',
            );
        }

        if (array_key_exists('@inertiajs/react', $combined)) {
            return new FrontendStackDetectionResult(
                stack: FrontendStack::InertiaReact,
                reason: 'Detected @inertiajs/react',
            );
        }

        return new FrontendStackDetectionResult(
            stack: FrontendStack::Blade,
            reason: 'No Inertia dependency detected',
        );
    }
}
