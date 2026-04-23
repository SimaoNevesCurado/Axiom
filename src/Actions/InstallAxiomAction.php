<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Data\InstallResult;
use SimaoCurado\Axiom\Data\InstallSelections;
use SimaoCurado\Axiom\Enums\AiGuidelinePreset;
use SimaoCurado\Axiom\Enums\AuthRoutesPreset;
use SimaoCurado\Axiom\Enums\DebugToolPreset;

final readonly class InstallAxiomAction
{
    public function __construct(private Filesystem $files) {}

    public function handle(InstallSelections $selections, string $basePath): InstallResult
    {
        $written = [];
        $skipped = [];

        if ($selections->installAiSkills) {
            $this->writeAiSkills(
                basePath: $basePath,
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                selectedSkills: $selections->aiSkills,
            );
        }

        if ($selections->installComposerScripts) {
            $this->writeComposerScripts(
                selections: $selections,
                basePath: $basePath,
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
            );
        }

        if ($this->composerDevDependencies($selections) !== []) {
            $this->writeComposerDevDependencies(
                selections: $selections,
                basePath: $basePath,
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
            );
        }

        if ($this->packageDevDependencies($selections) !== []) {
            $this->writePackageDevDependencies(
                selections: $selections,
                basePath: $basePath,
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
            );
        }

        $guidelines = $this->selectedAiGuidelines($selections);

        foreach ($guidelines as $guideline) {
            $this->writeFile(
                path: $this->aiGuidelinesPath($basePath, $guideline),
                content: $this->stub($this->aiGuidelinesStub($guideline, $basePath)),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );
        }

        if ($selections->installArchitectureGuidelines) {
            $this->writeFile(
                path: $basePath.'/.ai/architecture.md',
                content: $this->stub('docs/architecture.stub'),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );

            $this->writeFile(
                path: $basePath.'/app/Actions/.gitkeep',
                content: '',
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );

            $this->writeFile(
                path: $basePath.'/app/Dto/.gitkeep',
                content: '',
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );
        }

        if ($selections->installQualityGuidelines) {
            $this->writeFile(
                path: $basePath.'/.ai/quality.md',
                content: $this->stub('docs/quality.stub'),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );

            $this->writeFile(
                path: $basePath.'/phpstan.neon',
                content: $this->stub('quality/phpstan.neon.stub'),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );

            $this->writeFile(
                path: $basePath.'/rector.php',
                content: $this->stub('quality/rector.php.stub'),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );

            $this->writeFile(
                path: $basePath.'/pint.json',
                content: $this->stub('quality/pint.json.stub'),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );

            $this->writeFile(
                path: $basePath.'/tests/Unit/ArchTest.php',
                content: $this->stub('quality/ArchTest.php.stub'),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );
        }

        if ($selections->installStrictLaravelDefaults) {
            $this->writeFile(
                path: $basePath.'/config/axiom.php',
                content: $this->stub('config/axiom.stub'),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );

            $this->writeFile(
                path: $basePath.'/app/Providers/AxiomServiceProvider.php',
                content: $this->stub('providers/AxiomServiceProvider.stub'),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );

            $this->registerBootstrapProvider(
                basePath: $basePath,
                provider: 'App\\Providers\\AxiomServiceProvider::class',
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
            );
        }

        if ($selections->authRoutes === AuthRoutesPreset::AppManaged) {
            $fallbackToFortifyRoutes = $this->shouldFallbackToFortifyRoutes($basePath);

            $this->configureAppManagedAuthRoutes(
                basePath: $basePath,
                written: $written,
                skipped: $skipped,
                fallbackToFortifyRoutes: $fallbackToFortifyRoutes,
            );

            $this->configureFortifyProviderToIgnoreRoutes(
                basePath: $basePath,
                written: $written,
                skipped: $skipped,
                shouldIgnoreRoutes: ! $fallbackToFortifyRoutes,
            );
        }

        return new InstallResult($written, $skipped);
    }

    /**
     * @return list<AiGuidelinePreset>
     */
    private function selectedAiGuidelines(InstallSelections $selections): array
    {
        if ($selections->aiGuidelinePresets !== []) {
            $normalized = [];

            foreach ($selections->aiGuidelinePresets as $preset) {
                if ($preset === AiGuidelinePreset::None) {
                    continue;
                }

                if (! in_array($preset, $normalized, true)) {
                    $normalized[] = $preset;
                }
            }

            return $normalized;
        }

        if ($selections->aiGuidelines === AiGuidelinePreset::None) {
            return [];
        }

        return [$selections->aiGuidelines];
    }

    private function aiGuidelinesPath(string $basePath, AiGuidelinePreset $preset): string
    {
        return match ($preset) {
            AiGuidelinePreset::Boost, AiGuidelinePreset::Codex => $basePath.'/AGENTS.md',
            AiGuidelinePreset::Claude => $basePath.'/CLAUDE.md',
            AiGuidelinePreset::Gemini => $basePath.'/GEMINI.md',
            AiGuidelinePreset::Opencode => $basePath.'/OPENCODE.md',
            AiGuidelinePreset::None => $basePath.'/AGENTS.md',
        };
    }

    private function aiGuidelinesStub(AiGuidelinePreset $preset, string $basePath): string
    {
        $frontendProfile = $this->frontendProfile($basePath);

        if ($frontendProfile === 'vue') {
            return match ($preset) {
                AiGuidelinePreset::Boost, AiGuidelinePreset::Codex => 'ai/AGENTS.vue.stub',
                AiGuidelinePreset::Claude => 'ai/CLAUDE.vue.stub',
                AiGuidelinePreset::Gemini => 'ai/GEMINI.vue.stub',
                AiGuidelinePreset::Opencode => 'ai/OPENCODE.stub',
                AiGuidelinePreset::None => 'ai/AGENTS.vue.stub',
            };
        }

        if ($frontendProfile === 'react') {
            return match ($preset) {
                AiGuidelinePreset::Boost, AiGuidelinePreset::Codex => 'ai/AGENTS.react.stub',
                AiGuidelinePreset::Claude => 'ai/CLAUDE.react.stub',
                AiGuidelinePreset::Gemini => 'ai/GEMINI.react.stub',
                AiGuidelinePreset::Opencode => 'ai/OPENCODE.stub',
                AiGuidelinePreset::None => 'ai/AGENTS.react.stub',
            };
        }

        return match ($preset) {
            AiGuidelinePreset::Boost => 'ai/AGENTS.boost.stub',
            AiGuidelinePreset::Codex => 'ai/AGENTS.none.stub',
            AiGuidelinePreset::Claude => 'ai/CLAUDE.none.stub',
            AiGuidelinePreset::Gemini => 'ai/GEMINI.none.stub',
            AiGuidelinePreset::Opencode => 'ai/OPENCODE.stub',
            AiGuidelinePreset::None => 'ai/AGENTS.none.stub',
        };
    }

    /**
     * @return 'vue'|'react'|'none'
     */
    private function frontendProfile(string $basePath): string
    {
        $packagePath = $basePath.'/package.json';

        if (! $this->files->exists($packagePath)) {
            return 'none';
        }

        /** @var array<string, mixed>|null $package */
        $package = json_decode((string) $this->files->get($packagePath), true);

        if (! is_array($package)) {
            return 'none';
        }

        $dependencies = [];

        if (isset($package['dependencies']) && is_array($package['dependencies'])) {
            $dependencies = array_merge($dependencies, array_keys($package['dependencies']));
        }

        if (isset($package['devDependencies']) && is_array($package['devDependencies'])) {
            $dependencies = array_merge($dependencies, array_keys($package['devDependencies']));
        }

        if (in_array('react', $dependencies, true) || in_array('@inertiajs/react', $dependencies, true)) {
            return 'react';
        }

        if (in_array('vue', $dependencies, true) || in_array('@inertiajs/vue3', $dependencies, true)) {
            return 'vue';
        }

        return 'none';
    }

    /**
     * @param  list<string>  &$written
     * @param  list<string>  &$skipped
     */
    private function writeFile(
        string $path,
        string $content,
        bool $overwrite,
        array &$written,
        array &$skipped,
        string $basePath,
    ): void {
        if ($this->files->exists($path) && ! $overwrite) {
            $this->appendUnique($skipped, $this->relativePath($path, $basePath));

            return;
        }

        $directory = dirname($path);

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($path, $content);

        $this->appendUnique($written, $this->relativePath($path, $basePath));
    }

    private function stub(string $relativePath): string
    {
        return (string) $this->files->get(__DIR__.'/../../resources/stubs/'.$relativePath);
    }

    private function relativePath(string $path, string $basePath): string
    {
        return ltrim(str_replace($basePath, '', $path), '/');
    }

    /**
     * @param  list<string>  &$written
     * @param  list<string>  &$skipped
     */
    private function registerBootstrapProvider(
        string $basePath,
        string $provider,
        bool $overwrite,
        array &$written,
        array &$skipped,
    ): void {
        $providersPath = $basePath.'/bootstrap/providers.php';

        if (! $this->files->exists($providersPath)) {
            $this->appendUnique($skipped, 'bootstrap/providers.php');

            return;
        }

        $contents = (string) $this->files->get($providersPath);

        if (str_contains($contents, $provider)) {
            $this->appendUnique($skipped, 'bootstrap/providers.php');

            return;
        }

        $needle = '];';

        if (! str_contains($contents, $needle)) {
            $this->appendUnique($skipped, 'bootstrap/providers.php');

            return;
        }

        $updated = str_replace($needle, "    {$provider},\n];", $contents);

        if ($updated === $contents && ! $overwrite) {
            $this->appendUnique($skipped, 'bootstrap/providers.php');

            return;
        }

        $this->files->put($providersPath, $updated);

        $this->appendUnique($written, 'bootstrap/providers.php');
    }

    /**
     * @param  list<string>  &$written
     * @param  list<string>  &$skipped
     */
    private function configureFortifyProviderToIgnoreRoutes(
        string $basePath,
        array &$written,
        array &$skipped,
        bool $shouldIgnoreRoutes,
    ): void {
        $providerPath = $basePath.'/app/Providers/FortifyServiceProvider.php';

        if (! $this->files->exists($providerPath)) {
            $this->appendUnique($skipped, 'app/Providers/FortifyServiceProvider.php');

            return;
        }

        $contents = (string) $this->files->get($providerPath);
        $updated = $contents;

        if (! str_contains($updated, 'use Laravel\\Fortify\\Fortify;') && $shouldIgnoreRoutes) {
            if (preg_match_all('/^use\s+[^;]+;\s*$/m', $updated, $matches, PREG_OFFSET_CAPTURE) === false) {
                $this->appendUnique($skipped, 'app/Providers/FortifyServiceProvider.php');

                return;
            }

            if ($matches[0] !== []) {
                $last = $matches[0][array_key_last($matches[0])];
                $line = $last[0];
                $offset = $last[1] + strlen($line);
                $updated = substr($updated, 0, $offset)."\nuse Laravel\\Fortify\\Fortify;".substr($updated, $offset);
            } elseif (preg_match('/^namespace\s+[^;]+;\s*$/m', $updated, $namespace, PREG_OFFSET_CAPTURE) === 1) {
                $line = $namespace[0][0];
                $offset = $namespace[0][1] + strlen($line);
                $updated = substr($updated, 0, $offset)."\n\nuse Laravel\\Fortify\\Fortify;".substr($updated, $offset);
            } else {
                $this->appendUnique($skipped, 'app/Providers/FortifyServiceProvider.php');

                return;
            }
        }

        $updatedWithoutIgnoreRoutes = preg_replace(
            '/^\h*Fortify::ignoreRoutes\(\);\h*\R?/m',
            '',
            $updated,
        );

        if ($updatedWithoutIgnoreRoutes === null) {
            $this->appendUnique($skipped, 'app/Providers/FortifyServiceProvider.php');

            return;
        }

        $updated = $updatedWithoutIgnoreRoutes;

        if (! $shouldIgnoreRoutes) {
            if ($updated === $contents) {
                $this->appendUnique($skipped, 'app/Providers/FortifyServiceProvider.php');

                return;
            }

            $this->files->put($providerPath, $updated);
            $this->appendUnique($written, 'app/Providers/FortifyServiceProvider.php');

            return;
        }

        $updatedWithIgnoreRoutes = preg_replace(
            '/function\s+register\s*\([^)]*\)\s*(?::\s*void)?\s*\{\s*/m',
            "function register(): void\n    {\n        Fortify::ignoreRoutes();\n\n        ",
            $updated,
            1,
            $count,
        );

        if ($updatedWithIgnoreRoutes === null) {
            $this->appendUnique($skipped, 'app/Providers/FortifyServiceProvider.php');

            return;
        }

        if ($count === 0) {
            $updatedWithIgnoreRoutes = preg_replace(
                '/function\s+boot\s*\([^)]*\)\s*(?::\s*void)?\s*\{\s*/m',
                "function boot(): void\n    {\n        Fortify::ignoreRoutes();\n\n        ",
                $updated,
                1,
                $count,
            );

            if ($updatedWithIgnoreRoutes === null || $count === 0) {
                $this->appendUnique($skipped, 'app/Providers/FortifyServiceProvider.php');

                return;
            }
        }

        $updated = $updatedWithIgnoreRoutes;

        if ($updated === $contents) {
            $this->appendUnique($skipped, 'app/Providers/FortifyServiceProvider.php');

            return;
        }

        $this->files->put($providerPath, $updated);

        $this->appendUnique($written, 'app/Providers/FortifyServiceProvider.php');
    }

    /**
     * @param  list<string>  &$written
     * @param  list<string>  &$skipped
     */
    private function configureAppManagedAuthRoutes(
        string $basePath,
        array &$written,
        array &$skipped,
        bool $fallbackToFortifyRoutes,
    ): void {
        $routesPath = $basePath.'/routes/web.php';

        if (! $this->files->exists($routesPath)) {
            return;
        }

        $contents = (string) $this->files->get($routesPath);
        $updated = $this->stripAxiomRouteBlocks($contents);
        $hasChanges = $updated !== $contents;
        $routesContents = $this->routesContents($basePath, $updated);
        $missingAppManagedRoutes = $fallbackToFortifyRoutes
            ? []
            : $this->missingRoutes(
                $routesContents,
                $this->appManagedRouteDefinitions(),
            );
        $hasFortify = $this->hasFortifyInstalled($basePath);

        if ($missingAppManagedRoutes !== []) {
            $updated = $this->ensureAuthControllerImports($updated);
            $updated .= "\n\n".$this->renderRouteBlock(
                'Axiom app-managed auth routes',
                $missingAppManagedRoutes,
            )."\n";
            $hasChanges = true;
            $routesContents = $this->routesContents($basePath, $updated);
        }

        if ($hasFortify && ! $fallbackToFortifyRoutes) {
            $compatibility = $this->ensureFortifyCompatibilityRoutes($updated, $routesContents);

            if ($compatibility['changed']) {
                $updated = $compatibility['contents'];
                $hasChanges = true;
            }
        }

        if (! $hasChanges || $updated === $contents) {
            $this->appendUnique($skipped, 'routes/web.php');

            return;
        }

        $this->files->put($routesPath, $updated);
        $this->appendUnique($written, 'routes/web.php');
    }

    private function ensureAuthControllerImports(string $contents): string
    {
        $imports = [
            'use App\\Http\\Controllers\\SessionController;',
            'use App\\Http\\Controllers\\UserController;',
            'use App\\Http\\Controllers\\UserEmailResetNotificationController;',
            'use App\\Http\\Controllers\\UserEmailVerificationController;',
            'use App\\Http\\Controllers\\UserEmailVerificationNotificationController;',
            'use App\\Http\\Controllers\\UserPasswordController;',
            'use App\\Http\\Controllers\\UserTwoFactorAuthenticationController;',
        ];

        $missing = array_values(array_filter(
            $imports,
            static fn (string $import): bool => ! str_contains($contents, $import),
        ));

        if ($missing === []) {
            return $contents;
        }

        if (preg_match_all('/^use\s+[^;]+;\s*$/m', $contents, $matches, PREG_OFFSET_CAPTURE) !== false && $matches[0] !== []) {
            $last = $matches[0][array_key_last($matches[0])];
            $line = $last[0];
            $offset = $last[1] + strlen($line);

            return substr($contents, 0, $offset)."\n".implode("\n", $missing).substr($contents, $offset);
        }

        if (preg_match('/^declare\s*\(strict_types=1\);\s*$/m', $contents, $declare, PREG_OFFSET_CAPTURE) === 1) {
            $line = $declare[0][0];
            $offset = $declare[0][1] + strlen($line);

            return substr($contents, 0, $offset)."\n\n".implode("\n", $missing).substr($contents, $offset);
        }

        if (str_starts_with($contents, '<?php')) {
            return "<?php\n\n".implode("\n", $missing)."\n".ltrim(substr($contents, 5), "\n");
        }

        return implode("\n", $missing)."\n\n".$contents;
    }

    /**
     * @return array{contents: string, changed: bool}
     */
    private function ensureFortifyCompatibilityRoutes(string $contents, string $routesContents): array
    {
        $missingCompatibilityRoutes = $this->missingRoutes(
            $routesContents,
            $this->fortifyCompatibilityRouteDefinitions(),
        );

        if ($missingCompatibilityRoutes === []) {
            return ['contents' => $contents, 'changed' => false];
        }

        $updated = $this->ensureFortifyControllerImports($contents);
        $updated .= "\n\n".$this->renderRouteBlock(
            'Axiom Fortify compatibility routes',
            $missingCompatibilityRoutes,
        )."\n";

        return ['contents' => $updated, 'changed' => $updated !== $contents];
    }

    private function ensureFortifyControllerImports(string $contents): string
    {
        $imports = [
            'use Laravel\\Fortify\\Http\\Controllers\\ConfirmablePasswordController;',
            'use Laravel\\Fortify\\Http\\Controllers\\TwoFactorAuthenticatedSessionController;',
        ];

        $missing = array_values(array_filter(
            $imports,
            static fn (string $import): bool => ! str_contains($contents, $import),
        ));

        if ($missing === []) {
            return $contents;
        }

        if (preg_match_all('/^use\s+[^;]+;\s*$/m', $contents, $matches, PREG_OFFSET_CAPTURE) !== false && $matches[0] !== []) {
            $last = $matches[0][array_key_last($matches[0])];
            $line = $last[0];
            $offset = $last[1] + strlen($line);

            return substr($contents, 0, $offset)."\n".implode("\n", $missing).substr($contents, $offset);
        }

        if (preg_match('/^declare\s*\(strict_types=1\);\s*$/m', $contents, $declare, PREG_OFFSET_CAPTURE) === 1) {
            $line = $declare[0][0];
            $offset = $declare[0][1] + strlen($line);

            return substr($contents, 0, $offset)."\n\n".implode("\n", $missing).substr($contents, $offset);
        }

        if (str_starts_with($contents, '<?php')) {
            return "<?php\n\n".implode("\n", $missing)."\n".ltrim(substr($contents, 5), "\n");
        }

        return implode("\n", $missing)."\n\n".$contents;
    }

    private function hasFortifyInstalled(string $basePath): bool
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

    private function shouldFallbackToFortifyRoutes(string $basePath): bool
    {
        if ($this->hasAppManagedAuthControllers($basePath)) {
            return false;
        }

        return $this->files->exists($basePath.'/resources/js/pages/auth/ConfirmPassword.vue');
    }

    private function hasAppManagedAuthControllers(string $basePath): bool
    {
        $requiredControllers = [
            'SessionController.php',
            'UserController.php',
            'UserEmailResetNotificationController.php',
            'UserEmailVerificationController.php',
            'UserEmailVerificationNotificationController.php',
            'UserPasswordController.php',
            'UserTwoFactorAuthenticationController.php',
        ];

        foreach ($requiredControllers as $controller) {
            if (! $this->files->exists($basePath.'/app/Http/Controllers/'.$controller)) {
                return false;
            }
        }

        return true;
    }

    private function routesContents(string $basePath, ?string $webRoutesOverride = null): string
    {
        $routesDirectory = $basePath.'/routes';

        if (! $this->files->isDirectory($routesDirectory)) {
            return '';
        }

        $files = $this->files->files($routesDirectory);
        usort(
            $files,
            static fn (\SplFileInfo $left, \SplFileInfo $right): int => strcmp($left->getPathname(), $right->getPathname()),
        );

        $contents = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();

            if (! $this->files->exists($path)) {
                continue;
            }

            if ($webRoutesOverride !== null && $file->getFilename() === 'web.php') {
                $contents[] = $webRoutesOverride;

                continue;
            }

            $contents[] = (string) $this->files->get($path);
        }

        return implode("\n\n", $contents);
    }

    private function stripAxiomRouteBlocks(string $contents): string
    {
        if (! str_contains($contents, 'Axiom app-managed auth routes') && ! str_contains($contents, 'Axiom Fortify compatibility routes')) {
            return $contents;
        }

        $updated = preg_replace(
            '/\n?\/\/ Axiom app-managed auth routes\.\.\.[\s\S]*?(?=\n\/\/ Axiom Fortify compatibility routes\.\.\.|\z)/',
            "\n",
            $contents,
            1,
        );

        if ($updated === null) {
            $updated = $contents;
        }

        $updatedWithCompatibilityRemoved = preg_replace(
            '/\n?\/\/ Axiom Fortify compatibility routes\.\.\.[\s\S]*$/',
            "\n",
            $updated,
            1,
        );

        if ($updatedWithCompatibilityRemoved === null) {
            $updatedWithCompatibilityRemoved = $updated;
        }

        $normalized = preg_replace("/\n{3,}/", "\n\n", $updatedWithCompatibilityRemoved);

        if ($normalized === null) {
            return $updatedWithCompatibilityRemoved;
        }

        return rtrim($normalized)."\n";
    }

    private function hasNamedRoute(string $contents, string $name): bool
    {
        return preg_match("/->name\\(\\s*'".preg_quote($name, '/')."'\\s*\\)/m", $contents) === 1;
    }

    /**
     * @return list<array{name: string, middleware: 'guest'|'auth', method: 'get'|'post', uri: string, code: string}>
     */
    private function appManagedRouteDefinitions(): array
    {
        return [
            ['name' => 'login', 'middleware' => 'guest', 'method' => 'get', 'uri' => 'login', 'code' => "Route::get('login', [SessionController::class, 'create'])\n    ->name('login');"],
            ['name' => 'login.store', 'middleware' => 'guest', 'method' => 'post', 'uri' => 'login', 'code' => "Route::post('login', [SessionController::class, 'store'])\n    ->name('login.store');"],
            ['name' => 'register', 'middleware' => 'guest', 'method' => 'get', 'uri' => 'register', 'code' => "Route::get('register', [UserController::class, 'create'])\n    ->name('register');"],
            ['name' => 'register.store', 'middleware' => 'guest', 'method' => 'post', 'uri' => 'register', 'code' => "Route::post('register', [UserController::class, 'store'])\n    ->name('register.store');"],
            ['name' => 'password.request', 'middleware' => 'guest', 'method' => 'get', 'uri' => 'forgot-password', 'code' => "Route::get('forgot-password', [UserEmailResetNotificationController::class, 'create'])\n    ->name('password.request');"],
            ['name' => 'password.email', 'middleware' => 'guest', 'method' => 'post', 'uri' => 'forgot-password', 'code' => "Route::post('forgot-password', [UserEmailResetNotificationController::class, 'store'])\n    ->name('password.email');"],
            ['name' => 'password.reset', 'middleware' => 'guest', 'method' => 'get', 'uri' => 'reset-password/{token}', 'code' => "Route::get('reset-password/{token}', [UserPasswordController::class, 'create'])\n    ->name('password.reset');"],
            ['name' => 'password.update', 'middleware' => 'guest', 'method' => 'post', 'uri' => 'reset-password', 'code' => "Route::post('reset-password', [UserPasswordController::class, 'store'])\n    ->name('password.update');"],
            ['name' => 'verification.notice', 'middleware' => 'auth', 'method' => 'get', 'uri' => 'verify-email', 'code' => "Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])\n    ->name('verification.notice');"],
            ['name' => 'verification.send', 'middleware' => 'auth', 'method' => 'post', 'uri' => 'email/verification-notification', 'code' => "Route::post('email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])\n    ->middleware('throttle:6,1')\n    ->name('verification.send');"],
            ['name' => 'verification.verify', 'middleware' => 'auth', 'method' => 'get', 'uri' => 'verify-email/{id}/{hash}', 'code' => "Route::get('verify-email/{id}/{hash}', [UserEmailVerificationController::class, 'update'])\n    ->middleware(['signed', 'throttle:6,1'])\n    ->name('verification.verify');"],
            ['name' => 'two-factor.show', 'middleware' => 'auth', 'method' => 'get', 'uri' => 'settings/two-factor', 'code' => "Route::get('settings/two-factor', [UserTwoFactorAuthenticationController::class, 'show'])\n    ->name('two-factor.show');"],
            ['name' => 'logout', 'middleware' => 'auth', 'method' => 'post', 'uri' => 'logout', 'code' => "Route::post('logout', [SessionController::class, 'destroy'])\n    ->name('logout');"],
        ];
    }

    /**
     * @return list<array{name: string, middleware: 'guest'|'auth', method: 'get'|'post', uri: string, code: string}>
     */
    private function fortifyCompatibilityRouteDefinitions(): array
    {
        return [
            ['name' => 'two-factor.login', 'middleware' => 'guest', 'method' => 'get', 'uri' => 'two-factor-challenge', 'code' => "Route::get('two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'create'])\n    ->name('two-factor.login');"],
            ['name' => 'two-factor.login.store', 'middleware' => 'guest', 'method' => 'post', 'uri' => 'two-factor-challenge', 'code' => "Route::post('two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'store'])\n    ->name('two-factor.login.store');"],
            ['name' => 'password.confirm', 'middleware' => 'auth', 'method' => 'get', 'uri' => 'confirm-password', 'code' => "Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])\n    ->name('password.confirm');"],
            ['name' => 'password.confirm.store', 'middleware' => 'auth', 'method' => 'post', 'uri' => 'confirm-password', 'code' => "Route::post('confirm-password', [ConfirmablePasswordController::class, 'store'])\n    ->name('password.confirm.store');"],
        ];
    }

    /**
     * @param  list<array{name: string, middleware: 'guest'|'auth', method: 'get'|'post', uri: string, code: string}>  $definitions
     * @return list<array{name: string, middleware: 'guest'|'auth', method: 'get'|'post', uri: string, code: string}>
     */
    private function missingRoutes(string $routesContents, array $definitions): array
    {
        $missing = [];

        foreach ($definitions as $definition) {
            if ($this->hasNamedRoute($routesContents, $definition['name'])) {
                continue;
            }

            $methodPattern = preg_quote($definition['method'], '/');
            $uriPattern = preg_quote($definition['uri'], '/');
            $hasMethodAndUri = preg_match("/Route::{$methodPattern}\\(\\s*'{$uriPattern}'\\s*,/m", $routesContents) === 1;

            if ($hasMethodAndUri) {
                continue;
            }

            $missing[] = $definition;
        }

        return $missing;
    }

    /**
     * @param  list<array{name: string, middleware: 'guest'|'auth', method: 'get'|'post', uri: string, code: string}>  $definitions
     */
    private function renderRouteBlock(string $label, array $definitions): string
    {
        $guestRoutes = array_values(array_filter(
            $definitions,
            static fn (array $definition): bool => $definition['middleware'] === 'guest',
        ));
        $authRoutes = array_values(array_filter(
            $definitions,
            static fn (array $definition): bool => $definition['middleware'] === 'auth',
        ));
        $blocks = [];

        foreach ([$guestRoutes, $authRoutes] as $middlewareRoutes) {
            if ($middlewareRoutes === []) {
                continue;
            }

            $middleware = $middlewareRoutes[0]['middleware'];
            $routes = array_map(
                static fn (array $definition): string => preg_replace('/^/m', '    ', $definition['code']) ?? $definition['code'],
                $middlewareRoutes,
            );

            $blocks[] = "Route::middleware('{$middleware}')->group(function (): void {\n".implode("\n\n", $routes)."\n});";
        }

        return '// '.$label.'...'."\n".implode("\n\n", $blocks);
    }

    /**
     * @param  list<string>  &$written
     * @param  list<string>  &$skipped
     */
    private function writeAiSkills(
        string $basePath,
        bool $overwrite,
        array &$written,
        array &$skipped,
        array $selectedSkills,
    ): void {
        $skills = [
            'actions' => 'skills/actions.stub',
            'dto' => 'skills/dto.stub',
            'enum' => 'skills/enum.stub',
            'crud' => 'skills/crud.stub',
            'quality' => 'skills/quality.stub',
        ];

        foreach ($skills as $name => $stub) {
            if ($selectedSkills !== [] && ! in_array($name, $selectedSkills, true)) {
                continue;
            }

            $this->writeFile(
                path: $basePath.'/.ai/skills/'.$name.'.md',
                content: $this->stub($stub),
                overwrite: $overwrite,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );
        }
    }

    /**
     * @param  list<string>  &$written
     * @param  list<string>  &$skipped
     */
    private function writeComposerScripts(
        InstallSelections $selections,
        string $basePath,
        bool $overwrite,
        array &$written,
        array &$skipped,
    ): void {
        $composerPath = $basePath.'/composer.json';

        if (! $this->files->exists($composerPath)) {
            $this->appendUnique($skipped, 'composer.json');

            return;
        }

        /** @var array<string, mixed>|null $composer */
        $composer = json_decode((string) $this->files->get($composerPath), true);

        if (! is_array($composer)) {
            $this->appendUnique($skipped, 'composer.json');

            return;
        }

        $composer['scripts'] ??= [];

        if (! is_array($composer['scripts'])) {
            $this->appendUnique($skipped, 'composer.json');

            return;
        }

        $scripts = $this->composerScripts($selections, $basePath);
        $hasChanges = false;

        foreach ($scripts as $name => $command) {
            if (array_key_exists($name, $composer['scripts']) && ! $overwrite) {
                continue;
            }

            if (! array_key_exists($name, $composer['scripts']) || $composer['scripts'][$name] !== $command) {
                $composer['scripts'][$name] = $command;
                $hasChanges = true;
            }
        }

        if (! $hasChanges) {
            $this->appendUnique($skipped, 'composer.json');

            return;
        }

        ksort($composer['scripts']);

        $this->files->put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );

        $this->appendUnique($written, 'composer.json');
    }

    /**
     * @param  list<string>  &$written
     * @param  list<string>  &$skipped
     */
    private function writeComposerDevDependencies(
        InstallSelections $selections,
        string $basePath,
        bool $overwrite,
        array &$written,
        array &$skipped,
    ): void {
        $composerPath = $basePath.'/composer.json';

        if (! $this->files->exists($composerPath)) {
            $this->appendUnique($skipped, 'composer.json');

            return;
        }

        /** @var array<string, mixed>|null $composer */
        $composer = json_decode((string) $this->files->get($composerPath), true);

        if (! is_array($composer)) {
            $this->appendUnique($skipped, 'composer.json');

            return;
        }

        $composer['require-dev'] ??= [];

        if (! is_array($composer['require-dev'])) {
            $this->appendUnique($skipped, 'composer.json');

            return;
        }

        $dependencies = $this->composerDevDependencies($selections);

        $hasChanges = false;

        foreach ($dependencies as $name => $version) {
            if (array_key_exists($name, $composer['require-dev']) && ! $overwrite) {
                continue;
            }

            if (! array_key_exists($name, $composer['require-dev']) || $composer['require-dev'][$name] !== $version) {
                $composer['require-dev'][$name] = $version;
                $hasChanges = true;
            }
        }

        if (! $hasChanges) {
            $this->appendUnique($skipped, 'composer.json');

            return;
        }

        ksort($composer['require-dev']);

        $this->files->put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );

        $this->appendUnique($written, 'composer.json');
    }

    /**
     * @param  list<string>  &$written
     * @param  list<string>  &$skipped
     */
    private function writePackageDevDependencies(
        InstallSelections $selections,
        string $basePath,
        bool $overwrite,
        array &$written,
        array &$skipped,
    ): void {
        $packagePath = $basePath.'/package.json';

        if (! $this->files->exists($packagePath)) {
            $this->appendUnique($skipped, 'package.json');

            return;
        }

        /** @var array<string, mixed>|null $package */
        $package = json_decode((string) $this->files->get($packagePath), true);

        if (! is_array($package)) {
            $this->appendUnique($skipped, 'package.json');

            return;
        }

        $package['devDependencies'] ??= [];

        if (! is_array($package['devDependencies'])) {
            $this->appendUnique($skipped, 'package.json');

            return;
        }

        $dependencies = $this->packageDevDependencies($selections);

        $hasChanges = false;

        foreach ($dependencies as $name => $version) {
            if (array_key_exists($name, $package['devDependencies']) && ! $overwrite) {
                continue;
            }

            if (! array_key_exists($name, $package['devDependencies']) || $package['devDependencies'][$name] !== $version) {
                $package['devDependencies'][$name] = $version;
                $hasChanges = true;
            }
        }

        if (! $hasChanges) {
            $this->appendUnique($skipped, 'package.json');

            return;
        }

        ksort($package['devDependencies']);

        $this->files->put(
            $packagePath,
            json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );

        $this->appendUnique($written, 'package.json');
    }

    /**
     * @param  list<string>  &$items
     */
    private function appendUnique(array &$items, string $value): void
    {
        if (! in_array($value, $items, true)) {
            $items[] = $value;
        }
    }

    /**
     * @return array<string, string>
     */
    private function composerDevDependencies(InstallSelections $selections): array
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

        if ($installTypeCoverage) {
            $dependencies['pestphp/pest-plugin-type-coverage'] = '^4.0.3';
        }

        if ($selections->debugTool === DebugToolPreset::Debugbar) {
            $dependencies['barryvdh/laravel-debugbar'] = '^3.0';
        }

        if ($selections->debugTool === DebugToolPreset::Telescope) {
            $dependencies['laravel/telescope'] = '^5.0';
        }

        ksort($dependencies);

        return $dependencies;
    }

    /**
     * @return array<string, string>
     */
    private function packageDevDependencies(InstallSelections $selections): array
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

    /**
     * @return array<string, string|list<string>>
     */
    private function composerScripts(InstallSelections $selections, string $basePath): array
    {
        $hasFrontend = $this->files->exists($basePath.'/package.json');
        $useSsr = $hasFrontend && $selections->installSsr;

        $setup = [
            '@php -r "file_exists(\'.env\') || copy(\'.env.example\', \'.env\');"',
            '@configure:app-url',
            '@php artisan key:generate',
            '@php artisan migrate --force',
        ];

        if ($hasFrontend) {
            $setup[] = 'bun install';
            $setup[] = 'bun run build';
        }

        $dev = [
            'Composer\\Config::disableProcessTimeout',
        ];

        $dev[] = $hasFrontend
            ? ($useSsr
                ? 'bunx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74,#86efac" "php artisan serve" "php artisan queue:listen --tries=1" "php artisan pail --timeout=0" "bun run dev" "php artisan inertia:start-ssr" --names=server,queue,logs,vite,ssr --kill-others'
                : 'bunx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" "php artisan serve" "php artisan queue:listen --tries=1" "php artisan pail --timeout=0" "bun run dev" --names=server,queue,logs,vite --kill-others')
            : 'php artisan serve';

        $lint = [
            'rector',
            'pint --parallel',
        ];

        if ($hasFrontend) {
            $lint[] = 'bun run lint';
        }

        $testLint = [
            'pint --parallel --test',
            'rector --dry-run',
        ];

        if ($hasFrontend) {
            $testLint[] = 'bun run test:lint';
        }

        $testTypes = [
            'phpstan',
        ];

        if ($hasFrontend) {
            $testTypes[] = 'bun run test:types';
        }

        $updateRequirements = [
            'composer bump',
        ];

        if ($hasFrontend) {
            $updateRequirements[] = 'bunx npm-check-updates -u';
        }

        return [
            'configure:app-url' => [
                '@php -r "if (! file_exists(\'.env\')) { exit(0); } \$environment = file_get_contents(\'.env\'); \$directoryName = basename(getcwd()); \$slug = strtolower((string) preg_replace(\'/[^A-Za-z0-9]+/\', \'-\', \$directoryName)); \$slug = trim(\$slug, \'-\'); if (\$slug === \'\') { exit(0); } \$appUrl = \'http://\' . \$slug . \'.test\'; \$updatedEnvironment = preg_replace(\'/^APP_URL=.*/m\', \'APP_URL=\' . \$appUrl, \$environment, 1, \$replacements); if (\$replacements === 0) { \$updatedEnvironment .= PHP_EOL . \'APP_URL=\' . \$appUrl . PHP_EOL; } file_put_contents(\'.env\', \$updatedEnvironment);"',
            ],
            'dev' => $dev,
            'fix:rector' => 'rector',
            'lint' => $lint,
            'setup' => $setup,
            'test' => [
                '@test:type-coverage',
                '@test:unit',
                '@test:lint',
                '@test:rector',
                '@test:types',
            ],
            'test:lint' => $testLint,
            'test:rector' => 'rector --dry-run',
            'test:type-coverage' => 'pest --type-coverage --min=80',
            'test:types' => $testTypes,
            'test:unit' => 'pest --parallel',
            'update:requirements' => $updateRequirements,
        ];
    }
}
