<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Enums\FrontendStack;

final readonly class InstallAuthScaffoldAction
{
    public function __construct(private Filesystem $files) {}

    /**
     * @param  list<string>  &$written
     * @param  list<string>  &$skipped
     */
    public function handle(
        FrontendStack $stack,
        string $basePath,
        bool $overwrite,
        array &$written,
        array &$skipped,
    ): void {
        $this->pruneFortifyLeftovers($basePath);

        foreach ($this->manifest($stack) as $targetPath => $stubPath) {
            $this->writeFile(
                path: $basePath.'/'.$targetPath,
                content: $this->stub($stubPath),
                overwrite: $overwrite,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );
        }

        $this->registerWebRoutesInclude(
            basePath: $basePath,
            written: $written,
            skipped: $skipped,
        );
    }

    private function pruneFortifyLeftovers(string $basePath): void
    {
        $paths = [
            'app/Actions/DeleteUser.php',
            'app/Actions/UpdateUser.php',
            'app/Actions/UpdateUserPassword.php',
        ];

        foreach ($paths as $path) {
            $absolutePath = $basePath.'/'.$path;

            if ($this->files->exists($absolutePath)) {
                $this->files->delete($absolutePath);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function manifest(FrontendStack $stack): array
    {
        $common = [
            'routes/auth.php' => 'auth-scaffold/common/routes/auth.php.stub',
            'app/Actions/CreateUser.php' => 'auth-scaffold/common/actions/CreateUser.php.stub',
            'app/Actions/CreateUserEmailResetNotification.php' => 'auth-scaffold/common/actions/CreateUserEmailResetNotification.php.stub',
            'app/Actions/CreateUserEmailVerificationNotification.php' => 'auth-scaffold/common/actions/CreateUserEmailVerificationNotification.php.stub',
            'app/Actions/CreateUserPassword.php' => 'auth-scaffold/common/actions/CreateUserPassword.php.stub',
            'app/Http/Requests/CreateSessionRequest.php' => 'auth-scaffold/common/requests/CreateSessionRequest.php.stub',
            'app/Http/Requests/CreateUserRequest.php' => 'auth-scaffold/common/requests/CreateUserRequest.php.stub',
            'app/Http/Requests/CreateUserEmailResetNotificationRequest.php' => 'auth-scaffold/common/requests/CreateUserEmailResetNotificationRequest.php.stub',
            'app/Http/Requests/CreateUserPasswordRequest.php' => 'auth-scaffold/common/requests/CreateUserPasswordRequest.php.stub',
            'app/Rules/ValidEmail.php' => 'auth-scaffold/common/rules/ValidEmail.php.stub',
        ];

        return match ($stack) {
            FrontendStack::InertiaVue => $common + [
                'app/Http/Controllers/SessionController.php' => 'auth-scaffold/inertia-vue/controllers/SessionController.php.stub',
                'app/Http/Controllers/UserController.php' => 'auth-scaffold/inertia-vue/controllers/UserController.php.stub',
                'app/Http/Controllers/UserEmailResetNotificationController.php' => 'auth-scaffold/inertia-vue/controllers/UserEmailResetNotificationController.php.stub',
                'app/Http/Controllers/UserEmailVerificationController.php' => 'auth-scaffold/inertia-vue/controllers/UserEmailVerificationController.php.stub',
                'app/Http/Controllers/UserEmailVerificationNotificationController.php' => 'auth-scaffold/inertia-vue/controllers/UserEmailVerificationNotificationController.php.stub',
                'app/Http/Controllers/UserPasswordController.php' => 'auth-scaffold/inertia-vue/controllers/UserPasswordController.php.stub',
                'resources/js/pages/session/Create.vue' => 'auth-scaffold/inertia-vue/pages/session/Create.vue.stub',
                'resources/js/pages/user/Create.vue' => 'auth-scaffold/inertia-vue/pages/user/Create.vue.stub',
                'resources/js/pages/user-email-reset-notification/Create.vue' => 'auth-scaffold/inertia-vue/pages/user-email-reset-notification/Create.vue.stub',
                'resources/js/pages/user-email-verification-notification/Create.vue' => 'auth-scaffold/inertia-vue/pages/user-email-verification-notification/Create.vue.stub',
                'resources/js/pages/user-password/Create.vue' => 'auth-scaffold/inertia-vue/pages/user-password/Create.vue.stub',
            ],
            FrontendStack::InertiaReact => $common + [
                'app/Http/Controllers/SessionController.php' => 'auth-scaffold/inertia-react/controllers/SessionController.php.stub',
                'app/Http/Controllers/UserController.php' => 'auth-scaffold/inertia-react/controllers/UserController.php.stub',
                'app/Http/Controllers/UserEmailResetNotificationController.php' => 'auth-scaffold/inertia-react/controllers/UserEmailResetNotificationController.php.stub',
                'app/Http/Controllers/UserEmailVerificationController.php' => 'auth-scaffold/inertia-react/controllers/UserEmailVerificationController.php.stub',
                'app/Http/Controllers/UserEmailVerificationNotificationController.php' => 'auth-scaffold/inertia-react/controllers/UserEmailVerificationNotificationController.php.stub',
                'app/Http/Controllers/UserPasswordController.php' => 'auth-scaffold/inertia-react/controllers/UserPasswordController.php.stub',
                'resources/js/pages/session/create.tsx' => 'auth-scaffold/inertia-react/pages/session/create.tsx.stub',
                'resources/js/pages/user/create.tsx' => 'auth-scaffold/inertia-react/pages/user/create.tsx.stub',
                'resources/js/pages/user-email-reset-notification/create.tsx' => 'auth-scaffold/inertia-react/pages/user-email-reset-notification/create.tsx.stub',
                'resources/js/pages/user-email-verification-notification/create.tsx' => 'auth-scaffold/inertia-react/pages/user-email-verification-notification/create.tsx.stub',
                'resources/js/pages/user-password/create.tsx' => 'auth-scaffold/inertia-react/pages/user-password/create.tsx.stub',
            ],
            FrontendStack::Blade => $common + [
                'app/Http/Controllers/SessionController.php' => 'auth-scaffold/blade/controllers/SessionController.php.stub',
                'app/Http/Controllers/UserController.php' => 'auth-scaffold/blade/controllers/UserController.php.stub',
                'app/Http/Controllers/UserEmailResetNotificationController.php' => 'auth-scaffold/blade/controllers/UserEmailResetNotificationController.php.stub',
                'app/Http/Controllers/UserEmailVerificationController.php' => 'auth-scaffold/blade/controllers/UserEmailVerificationController.php.stub',
                'app/Http/Controllers/UserEmailVerificationNotificationController.php' => 'auth-scaffold/blade/controllers/UserEmailVerificationNotificationController.php.stub',
                'app/Http/Controllers/UserPasswordController.php' => 'auth-scaffold/blade/controllers/UserPasswordController.php.stub',
                'resources/views/session/create.blade.php' => 'auth-scaffold/blade/views/session/create.blade.php.stub',
                'resources/views/user/create.blade.php' => 'auth-scaffold/blade/views/user/create.blade.php.stub',
                'resources/views/user-email-reset-notification/create.blade.php' => 'auth-scaffold/blade/views/user-email-reset-notification/create.blade.php.stub',
                'resources/views/user-email-verification-notification/create.blade.php' => 'auth-scaffold/blade/views/user-email-verification-notification/create.blade.php.stub',
                'resources/views/user-password/create.blade.php' => 'auth-scaffold/blade/views/user-password/create.blade.php.stub',
            ],
        };
    }

    private function stub(string $relativePath): string
    {
        return (string) $this->files->get(__DIR__.'/../../resources/stubs/'.$relativePath);
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

    private function relativePath(string $path, string $basePath): string
    {
        return ltrim(str_replace($basePath, '', $path), '/');
    }

    /**
     * @param  list<string>  &$written
     * @param  list<string>  &$skipped
     */
    private function registerWebRoutesInclude(
        string $basePath,
        array &$written,
        array &$skipped,
    ): void {
        $webRoutesPath = $basePath.'/routes/web.php';

        if (! $this->files->exists($webRoutesPath)) {
            $this->appendUnique($skipped, 'routes/web.php');

            return;
        }

        $contents = (string) $this->files->get($webRoutesPath);
        $include = "require __DIR__.'/auth.php';";

        if (str_contains($contents, $include)) {
            $this->appendUnique($skipped, 'routes/web.php');

            return;
        }

        $updated = rtrim($contents)."\n\n".$include."\n";

        $this->files->put($webRoutesPath, $updated);

        $this->appendUnique($written, 'routes/web.php');
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
}
