<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class CleanLegacyFortifyAuthAction
{
    public function __construct(private Filesystem $files) {}

    public function handle(InstallContext $context): void
    {
        $this->deleteLegacyFortifyActions($context);
        $this->removeWelcomeRegistrationFeatureFlag($context);
    }

    private function deleteLegacyFortifyActions(InstallContext $context): void
    {
        $legacyDirectory = $context->basePath.'/app/Actions/Fortify';
        $paths = [
            'app/Actions/Fortify/CreateNewUser.php',
            'app/Actions/Fortify/ResetUserPassword.php',
            'app/Actions/Fortify/.gitkeep',
        ];

        foreach ($paths as $relativePath) {
            $path = $context->basePath.'/'.$relativePath;

            if (! $this->files->exists($path)) {
                continue;
            }

            $this->files->delete($path);
            $context->recordWritten($relativePath);
        }

        if ($this->files->isDirectory($legacyDirectory) && $this->isEmptyDirectory($legacyDirectory)) {
            $this->files->deleteDirectory($legacyDirectory);
            $context->recordWritten('app/Actions/Fortify');
        }
    }

    private function removeWelcomeRegistrationFeatureFlag(InstallContext $context): void
    {
        $routesPath = $context->basePath.'/routes/web.php';

        if (! $this->files->exists($routesPath)) {
            return;
        }

        $contents = (string) $this->files->get($routesPath);
        $updated = preg_replace(
            "/Route::inertia\\(\\s*'\\/'\\s*,\\s*'([^']+)'\\s*,\\s*\\[\\s*'canRegister'\\s*=>\\s*Features::enabled\\(Features::registration\\(\\)\\),?\\s*\\]\\s*\\)(\\s*->name\\(\\s*'home'\\s*\\);)/s",
            "Route::inertia('/', '$1')$2",
            $contents,
            1,
        );

        if ($updated === null || $updated === $contents) {
            return;
        }

        if (! str_contains($updated, 'Features::')) {
            $updated = $this->removeUseImport($updated, 'use Laravel\\Fortify\\Features;');
        }

        $this->files->put($routesPath, $updated);
        $context->recordWritten('routes/web.php');
    }

    private function removeUseImport(string $contents, string $import): string
    {
        $updated = preg_replace('/^'.preg_quote($import, '/')."\s*\n/m", '', $contents, 1);

        return $updated ?? $contents;
    }

    private function isEmptyDirectory(string $path): bool
    {
        $items = scandir($path);

        if ($items === false) {
            return false;
        }

        return array_values(array_diff($items, ['.', '..'])) === [];
    }
}
