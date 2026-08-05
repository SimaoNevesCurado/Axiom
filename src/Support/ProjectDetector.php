<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Support;

final class ProjectDetector
{
    public function hasSsrEntrypoint(): bool
    {
        $paths = [
            'resources/js/ssr.js',
            'resources/js/ssr.jsx',
            'resources/js/ssr.ts',
            'resources/js/ssr.tsx',
        ];

        foreach ($paths as $path) {
            if (file_exists(base_path($path))) {
                return true;
            }
        }

        return false;
    }

    public function hasFortifyInstalled(): bool
    {
        $composerPath = base_path('composer.json');

        if (! file_exists($composerPath)) {
            return false;
        }

        /** @var array<string, mixed>|null $composer */
        $composer = json_decode((string) file_get_contents($composerPath), true);

        if (! is_array($composer)) {
            return false;
        }

        return isset($composer['require']['laravel/fortify']);
    }

    public function hasFortifyProviderRegistered(): bool
    {
        $providersPath = base_path('bootstrap/providers.php');

        if (! file_exists($providersPath)) {
            return false;
        }

        return str_contains(
            (string) file_get_contents($providersPath),
            'App\\Providers\\FortifyServiceProvider::class',
        );
    }

    public function hasAuthScaffold(): bool
    {
        $paths = [
            base_path('routes/auth.php'),
            base_path('config/fortify.php'),
            base_path('app/Providers/FortifyServiceProvider.php'),
            base_path('app/Http/Controllers/SessionController.php'),
            base_path('app/Http/Controllers/UserController.php'),
            base_path('resources/js/pages/auth'),
            base_path('resources/js/pages/session'),
            base_path('resources/js/Pages/Auth'),
            base_path('resources/js/Pages/Session'),
        ];

        foreach ($paths as $path) {
            if (is_dir($path) || file_exists($path)) {
                return true;
            }
        }

        $webRoutesPath = base_path('routes/web.php');

        if (! file_exists($webRoutesPath)) {
            return false;
        }

        $webRoutes = (string) file_get_contents($webRoutesPath);

        return str_contains($webRoutes, "->name('login')")
            || str_contains($webRoutes, "->name('login.store')")
            || str_contains($webRoutes, "->name('register')")
            || str_contains($webRoutes, "->name('password.request')");
    }

    public function hasAppManagedAuthRoutes(): bool
    {
        foreach (['routes/web.php', 'routes/auth.php'] as $routeFile) {
            $path = base_path($routeFile);

            if (! file_exists($path)) {
                continue;
            }

            $routes = (string) file_get_contents($path);

            if (str_contains($routes, "->name('login')")
                || str_contains($routes, "->name('login.store')")
                || str_contains($routes, "->name('register')")
                || str_contains($routes, "->name('password.request')")) {
                return true;
            }
        }

        return false;
    }

    public function hasPackageJson(): bool
    {
        return file_exists(base_path('package.json'));
    }
}
