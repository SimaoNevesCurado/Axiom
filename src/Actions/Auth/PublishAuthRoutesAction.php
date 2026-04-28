<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Actions\DetectFrontendStackAction;
use SimaoCurado\Axiom\Actions\EnsureUseImportsAction;
use SimaoCurado\Axiom\Data\AuthRouteDefinition;
use SimaoCurado\Axiom\Enums\AuthRouteMiddleware;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class PublishAuthRoutesAction
{
    private DetectFrontendStackAction $detectFrontendStack;

    private EnsureUseImportsAction $ensureUseImports;

    private ResolveAuthPageNamesAction $resolveAuthPageNames;

    public function __construct(private Filesystem $files)
    {
        $this->detectFrontendStack = new DetectFrontendStackAction($files);
        $this->ensureUseImports = new EnsureUseImportsAction;
        $this->resolveAuthPageNames = new ResolveAuthPageNamesAction;
    }

    public function handle(InstallContext $context): void
    {
        $routesPath = $context->basePath.'/routes/web.php';

        if (! $this->files->exists($routesPath)) {
            return;
        }

        $contents = (string) $this->files->get($routesPath);
        $updated = $this->stripAxiomRouteBlocks($contents);
        $hasChanges = $updated !== $contents;
        $routesContents = $this->routesContents($context->basePath, $updated);
        $missingRoutes = $this->missingRoutes($routesContents, $this->routeDefinitions($context));

        if ($missingRoutes !== []) {
            $updated = $this->ensureControllerImports($updated);
            $updated .= "\n\n".$this->renderRouteBlock('Axiom app-managed auth routes', $missingRoutes)."\n";
            $hasChanges = true;
        }

        if (! $hasChanges || $updated === $contents) {
            $context->recordSkipped('routes/web.php');

            return;
        }

        $this->files->put($routesPath, $updated);
        $context->recordWritten('routes/web.php');
    }

    private function ensureControllerImports(string $contents): string
    {
        return $this->ensureUseImports->handle($contents, [
            'use App\\Http\\Controllers\\SessionController;',
            'use App\\Http\\Controllers\\UserController;',
            'use App\\Http\\Controllers\\UserEmailResetNotificationController;',
            'use App\\Http\\Controllers\\UserEmailVerificationController;',
            'use App\\Http\\Controllers\\UserEmailVerificationNotificationController;',
            'use App\\Http\\Controllers\\UserPasswordController;',
            'use App\\Http\\Controllers\\UserProfileController;',
            'use App\\Http\\Controllers\\UserTwoFactorAuthenticationController;',
            'use Inertia\\Inertia;',
        ]);
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

        $updated = preg_replace(
            '/\n?\/\/ Axiom Fortify compatibility routes\.\.\.[\s\S]*$/',
            "\n",
            $updated,
            1,
        ) ?? $updated;

        $normalized = preg_replace("/\n{3,}/", "\n\n", $updated);

        if ($normalized === null) {
            return $updated;
        }

        return rtrim($normalized)."\n";
    }

    /**
     * @return list<AuthRouteDefinition>
     */
    private function routeDefinitions(InstallContext $context): array
    {
        $pages = $this->resolveAuthPageNames->handle(
            $this->detectFrontendStack->handle($context->basePath),
        );

        return [
            new AuthRouteDefinition('user.destroy', AuthRouteMiddleware::Auth, 'delete', 'user', "// User...\nRoute::delete('user', [UserController::class, 'destroy'])\n    ->name('user.destroy');"),
            new AuthRouteDefinition(null, AuthRouteMiddleware::Auth, 'redirect', 'settings', "// User Profile...\nRoute::redirect('settings', '/settings/profile');"),
            new AuthRouteDefinition('user-profile.edit', AuthRouteMiddleware::Auth, 'get', 'settings/profile', "Route::get('settings/profile', [UserProfileController::class, 'edit'])\n    ->name('user-profile.edit');"),
            new AuthRouteDefinition('user-profile.update', AuthRouteMiddleware::Auth, 'patch', 'settings/profile', "Route::patch('settings/profile', [UserProfileController::class, 'update'])\n    ->name('user-profile.update');"),
            new AuthRouteDefinition('password.edit', AuthRouteMiddleware::Auth, 'get', 'settings/password', "// User Password...\nRoute::get('settings/password', [UserPasswordController::class, 'edit'])\n    ->name('password.edit');"),
            new AuthRouteDefinition('password.update', AuthRouteMiddleware::Auth, 'put', 'settings/password', "Route::put('settings/password', [UserPasswordController::class, 'update'])\n    ->middleware('throttle:6,1')\n    ->name('password.update');"),
            new AuthRouteDefinition('appearance.edit', AuthRouteMiddleware::Auth, 'get', 'settings/appearance', "// Appearance...\nRoute::get('settings/appearance', fn () => Inertia::render('{$pages['appearance']}'))\n    ->name('appearance.edit');"),
            new AuthRouteDefinition('two-factor.show', AuthRouteMiddleware::Auth, 'get', 'settings/two-factor', "// User Two-Factor Authentication...\nRoute::get('settings/two-factor', [UserTwoFactorAuthenticationController::class, 'show'])\n    ->name('two-factor.show');"),
            new AuthRouteDefinition('register', AuthRouteMiddleware::Guest, 'get', 'register', "// User...\nRoute::get('register', [UserController::class, 'create'])\n    ->name('register');"),
            new AuthRouteDefinition('register.store', AuthRouteMiddleware::Guest, 'post', 'register', "Route::post('register', [UserController::class, 'store'])\n    ->name('register.store');"),
            new AuthRouteDefinition('password.reset', AuthRouteMiddleware::Guest, 'get', 'reset-password/{token}', "// User Password...\nRoute::get('reset-password/{token}', [UserPasswordController::class, 'create'])\n    ->name('password.reset');"),
            new AuthRouteDefinition('password.store', AuthRouteMiddleware::Guest, 'post', 'reset-password', "Route::post('reset-password', [UserPasswordController::class, 'store'])\n    ->name('password.store');"),
            new AuthRouteDefinition('password.request', AuthRouteMiddleware::Guest, 'get', 'forgot-password', "// User Email Reset Notification...\nRoute::get('forgot-password', [UserEmailResetNotificationController::class, 'create'])\n    ->name('password.request');"),
            new AuthRouteDefinition('password.email', AuthRouteMiddleware::Guest, 'post', 'forgot-password', "Route::post('forgot-password', [UserEmailResetNotificationController::class, 'store'])\n    ->name('password.email');"),
            new AuthRouteDefinition('login', AuthRouteMiddleware::Guest, 'get', 'login', "// Session...\nRoute::get('login', [SessionController::class, 'create'])\n    ->name('login');"),
            new AuthRouteDefinition('login.store', AuthRouteMiddleware::Guest, 'post', 'login', "Route::post('login', [SessionController::class, 'store'])\n    ->name('login.store');"),
            new AuthRouteDefinition('verification.notice', AuthRouteMiddleware::Auth, 'get', 'verify-email', "// User Email Verification...\nRoute::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])\n    ->name('verification.notice');"),
            new AuthRouteDefinition('verification.send', AuthRouteMiddleware::Auth, 'post', 'email/verification-notification', "Route::post('email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])\n    ->middleware('throttle:6,1')\n    ->name('verification.send');"),
            new AuthRouteDefinition('verification.verify', AuthRouteMiddleware::Auth, 'get', 'verify-email/{id}/{hash}', "// User Email Verification...\nRoute::get('verify-email/{id}/{hash}', [UserEmailVerificationController::class, 'update'])\n    ->middleware(['signed', 'throttle:6,1'])\n    ->name('verification.verify');"),
            new AuthRouteDefinition('logout', AuthRouteMiddleware::Auth, 'post', 'logout', "// Session...\nRoute::post('logout', [SessionController::class, 'destroy'])\n    ->name('logout');"),
        ];
    }

    /**
     * @param  list<AuthRouteDefinition>  $definitions
     * @return list<AuthRouteDefinition>
     */
    private function missingRoutes(string $routesContents, array $definitions): array
    {
        $missing = [];

        foreach ($definitions as $definition) {
            if ($definition->name !== null && $this->hasNamedRoute($routesContents, $definition->name)) {
                continue;
            }

            $methodPattern = preg_quote($definition->method, '/');
            $uriPattern = preg_quote($definition->uri, '/');
            $hasMethodAndUri = preg_match("/Route::{$methodPattern}\\(\\s*'{$uriPattern}'\\s*,/m", $routesContents) === 1;

            if ($hasMethodAndUri) {
                continue;
            }

            $missing[] = $definition;
        }

        return $missing;
    }

    private function hasNamedRoute(string $contents, string $name): bool
    {
        return preg_match("/->name\\(\\s*'".preg_quote($name, '/')."'\\s*\\)/m", $contents) === 1;
    }

    /**
     * @param  list<AuthRouteDefinition>  $definitions
     */
    private function renderRouteBlock(string $label, array $definitions): string
    {
        $blocks = [];
        $currentMiddleware = null;
        $currentRoutes = [];

        foreach ($definitions as $definition) {
            if ($currentMiddleware !== null && $definition->middleware !== $currentMiddleware) {
                $blocks[] = $this->renderMiddlewareRouteGroup($currentMiddleware, $currentRoutes);
                $currentRoutes = [];
            }

            $currentMiddleware = $definition->middleware;
            $currentRoutes[] = $definition->code;
        }

        if ($currentMiddleware !== null && $currentRoutes !== []) {
            $blocks[] = $this->renderMiddlewareRouteGroup($currentMiddleware, $currentRoutes);
        }

        return '// '.$label.'...'."\n".implode("\n\n", $blocks);
    }

    /**
     * @param  list<string>  $routes
     */
    private function renderMiddlewareRouteGroup(AuthRouteMiddleware $middleware, array $routes): string
    {
        $indentedRoutes = array_map(
            static fn (string $route): string => preg_replace('/^/m', '    ', $route) ?? $route,
            $routes,
        );

        return "Route::middleware('{$middleware->value}')->group(function (): void {\n".implode("\n\n", $indentedRoutes)."\n});";
    }
}
