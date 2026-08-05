<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Actions\EnsureUseImportsAction;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class CleanLegacyFortifyAuthAction
{
    private EnsureUseImportsAction $ensureUseImports;

    public function __construct(private Filesystem $files)
    {
        $this->ensureUseImports = new EnsureUseImportsAction;
    }

    public function handle(InstallContext $context): void
    {
        $this->deleteLegacyFortifyActions($context);
        $this->removeWelcomeRegistrationFeatureFlag($context);
        $this->removeWelcomeRegistrationProp($context);
    }

    private function deleteLegacyFortifyActions(InstallContext $context): void
    {
        $legacyDirectory = $context->basePath.'/app/Actions/Fortify';
        $paths = [
            'app/Actions/Fortify/CreateNewUser.php',
            'app/Actions/Fortify/ResetUserPassword.php',
            'app/Actions/Fortify/.gitkeep',
            'resources/js/pages/auth/ConfirmPassword.vue',
            'resources/js/pages/auth/ForgotPassword.vue',
            'resources/js/pages/auth/Login.vue',
            'resources/js/pages/auth/Register.vue',
            'resources/js/pages/auth/ResetPassword.vue',
            'resources/js/pages/auth/TwoFactorChallenge.vue',
            'resources/js/pages/auth/VerifyEmail.vue',
        ];

        foreach ($paths as $relativePath) {
            $path = $context->basePath.'/'.$relativePath;

            if (! $this->files->exists($path)) {
                continue;
            }

            $context->deletePath($this->files, $relativePath);
        }

        if ($this->files->isDirectory($legacyDirectory) && $this->isEmptyDirectory($legacyDirectory)) {
            $context->deletePath($this->files, 'app/Actions/Fortify');
        }

        $legacyPagesDirectory = $context->basePath.'/resources/js/pages/auth';

        if ($this->files->isDirectory($legacyPagesDirectory) && $this->isEmptyDirectory($legacyPagesDirectory)) {
            $context->deletePath($this->files, 'resources/js/pages/auth');
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
            "Route::get('/', fn () => Inertia::render('$1'))$2",
            $contents,
            1,
        );

        if ($updated === null || $updated === $contents) {
            return;
        }

        $updated = $this->ensureUseImports->handle($updated, [
            'use Inertia\\Inertia;',
        ]);

        if (! str_contains($updated, 'Features::')) {
            $updated = $this->removeUseImport($updated, 'use Laravel\\Fortify\\Features;');
        }

        $context->putFile($this->files, 'routes/web.php', $updated);
    }

    private function removeUseImport(string $contents, string $import): string
    {
        $updated = preg_replace('/^'.preg_quote($import, '/')."\s*\n/m", '', $contents, 1);

        return $updated ?? $contents;
    }

    private function removeWelcomeRegistrationProp(InstallContext $context): void
    {
        $paths = [
            'resources/js/pages/Welcome.vue',
            'resources/js/Pages/Welcome.vue',
            'resources/js/pages/Welcome.tsx',
            'resources/js/Pages/Welcome.tsx',
            'resources/js/pages/welcome.tsx',
            'resources/js/Pages/Welcome.jsx',
        ];

        foreach ($paths as $relativePath) {
            $path = $context->basePath.'/'.$relativePath;

            if (! $this->files->exists($path)) {
                continue;
            }

            $contents = (string) $this->files->get($path);
            $updated = $this->stripWelcomeRegistrationProp($contents);

            if ($updated === $contents) {
                continue;
            }

            $context->putFile($this->files, $relativePath, $updated);
        }
    }

    private function stripWelcomeRegistrationProp(string $contents): string
    {
        $updated = preg_replace('/^[ \t]*canRegister\??:\s*boolean;?\R/m', '', $contents) ?? $contents;
        $updated = str_replace(' v-if="canRegister"', '', $updated);
        $updated = str_replace(" v-if='canRegister'", '', $updated);
        $updated = preg_replace('/\(\s*\{\s*canRegister\s*}\s*:\s*[^)]*\)/m', '()', $updated) ?? $updated;
        $updated = preg_replace(
            '/\{\s*canRegister\s*&&\s*\(\s*(<Link\b[\s\S]*?href=\{route\([\'\"]register[\'\"]\)\}[\s\S]*?<\/Link>)\s*\)\s*}/m',
            '$1',
            $updated,
        ) ?? $updated;

        return $updated;
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
