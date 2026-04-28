<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Actions\DetectFrontendStackAction;
use SimaoCurado\Axiom\Actions\RegisterBootstrapProviderAction;
use SimaoCurado\Axiom\Actions\ResolveStubPathAction;
use SimaoCurado\Axiom\Actions\WriteFileAction;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class PublishFortifyScaffoldAction
{
    private DetectFrontendStackAction $detectFrontendStack;

    private RegisterBootstrapProviderAction $registerProvider;

    private ResolveAuthPageNamesAction $resolveAuthPageNames;

    private ResolveStubPathAction $stubs;

    private WriteFileAction $writeFile;

    public function __construct(Filesystem $files)
    {
        $this->detectFrontendStack = new DetectFrontendStackAction($files);
        $this->registerProvider = new RegisterBootstrapProviderAction($files);
        $this->resolveAuthPageNames = new ResolveAuthPageNamesAction;
        $this->stubs = new ResolveStubPathAction($files);
        $this->writeFile = new WriteFileAction($files);
    }

    public function handle(InstallContext $context): void
    {
        $this->writeFile->handle($context, 'config/fortify.php', $this->stubs->contents('auth/config/fortify.stub'));
        $this->writeFile->handle(
            $context,
            'app/Providers/FortifyServiceProvider.php',
            $this->stubs->render('auth/providers/FortifyServiceProvider.stub', $this->replacements($context)),
        );

        $this->registerProvider->handle($context, 'App\\Providers\\FortifyServiceProvider::class');
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
