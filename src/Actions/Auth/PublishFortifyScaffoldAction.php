<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Actions\DetectFrontendStackAction;
use SimaoCurado\Axiom\Actions\RegisterBootstrapProviderAction;
use SimaoCurado\Axiom\Actions\ResolveStubPathAction;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class PublishFortifyScaffoldAction
{
    private Filesystem $files;

    private DetectFrontendStackAction $detectFrontendStack;

    private RegisterBootstrapProviderAction $registerProvider;

    private ResolveAuthPageNamesAction $resolveAuthPageNames;

    private ResolveStubPathAction $stubs;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        $this->detectFrontendStack = new DetectFrontendStackAction($files);
        $this->registerProvider = new RegisterBootstrapProviderAction($files);
        $this->resolveAuthPageNames = new ResolveAuthPageNamesAction;
        $this->stubs = new ResolveStubPathAction($files);
    }

    public function handle(InstallContext $context): void
    {
        $this->publishFortifyConfig($context);
        $this->publishFortifyProvider($context);

        $this->registerProvider->handle($context, 'App\\Providers\\FortifyServiceProvider::class');
    }

    private function publishFortifyConfig(InstallContext $context): void
    {
        $relativePath = 'config/fortify.php';
        $path = $context->basePath.'/'.$relativePath;
        $content = $this->stubs->contents('auth/config/fortify.stub');

        if (! $this->files->exists($path)) {
            $this->put($context, $relativePath, $content);

            return;
        }

        $existing = (string) $this->files->get($path);

        if (! $context->selections->overwriteFiles && ! $this->isLegacyFortifyConfig($existing)) {
            $context->recordSkipped($relativePath);

            return;
        }

        if ($existing === $content) {
            $context->recordSkipped($relativePath);

            return;
        }

        $this->put($context, $relativePath, $content);
    }

    private function publishFortifyProvider(InstallContext $context): void
    {
        $relativePath = 'app/Providers/FortifyServiceProvider.php';
        $path = $context->basePath.'/'.$relativePath;
        $content = $this->stubs->render('auth/providers/FortifyServiceProvider.stub', $this->replacements($context));

        if (! $this->files->exists($path)) {
            $this->put($context, $relativePath, $content);

            return;
        }

        $existing = (string) $this->files->get($path);

        if (! $context->selections->overwriteFiles && ! $this->isLegacyFortifyProvider($existing)) {
            $context->recordSkipped($relativePath);

            return;
        }

        if ($existing === $content) {
            $context->recordSkipped($relativePath);

            return;
        }

        $this->put($context, $relativePath, $content);
    }

    private function isLegacyFortifyConfig(string $contents): bool
    {
        return preg_match('/^\s*Features::(?:registration|resetPasswords|emailVerification|updateProfileInformation|updatePasswords)\(\),/m', $contents) === 1;
    }

    private function isLegacyFortifyProvider(string $contents): bool
    {
        return str_contains($contents, 'App\\Actions\\Fortify\\CreateNewUser')
            || str_contains($contents, 'App\\Actions\\Fortify\\ResetUserPassword')
            || str_contains($contents, 'Fortify::loginView(')
            || str_contains($contents, 'Fortify::registerView(')
            || str_contains($contents, 'Fortify::resetPasswordView(')
            || str_contains($contents, 'Fortify::verifyEmailView(');
    }

    private function put(InstallContext $context, string $relativePath, string $content): void
    {
        $path = $context->basePath.'/'.$relativePath;
        $directory = dirname($path);

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($path, $content);
        $context->recordWritten($relativePath);
    }

    /**
     * @return array<string, string>
     */
    private function replacements(InstallContext $context): array
    {
        $pages = $this->resolveAuthPageNames->handle(
            $this->detectFrontendStack->handle($context->basePath),
        );

        return [
            '{{ sessionCreatePage }}' => $pages['sessionCreate'],
            '{{ userCreatePage }}' => $pages['userCreate'],
            '{{ userEmailResetNotificationCreatePage }}' => $pages['userEmailResetNotificationCreate'],
            '{{ userEmailVerificationNotificationCreatePage }}' => $pages['userEmailVerificationNotificationCreate'],
            '{{ userPasswordCreatePage }}' => $pages['userPasswordCreate'],
            '{{ userPasswordEditPage }}' => $pages['userPasswordEdit'],
            '{{ userProfileEditPage }}' => $pages['userProfileEdit'],
            '{{ userTwoFactorAuthenticationShowPage }}' => $pages['userTwoFactorAuthenticationShow'],
            '{{ userTwoFactorAuthenticationChallengeShowPage }}' => $pages['userTwoFactorAuthenticationChallengeShow'],
            '{{ userPasswordConfirmationCreatePage }}' => $pages['userPasswordConfirmationCreate'],
        ];
    }
}
