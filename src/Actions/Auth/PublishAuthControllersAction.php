<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Actions\DetectFrontendStackAction;
use SimaoCurado\Axiom\Actions\ResolveStubPathAction;
use SimaoCurado\Axiom\Actions\WriteFileAction;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class PublishAuthControllersAction
{
    private DetectFrontendStackAction $detectFrontendStack;

    private ResolveAuthPageNamesAction $resolveAuthPageNames;

    private ResolveStubPathAction $stubs;

    private WriteFileAction $writeFile;

    public function __construct(Filesystem $files)
    {
        $this->detectFrontendStack = new DetectFrontendStackAction($files);
        $this->resolveAuthPageNames = new ResolveAuthPageNamesAction;
        $this->stubs = new ResolveStubPathAction($files);
        $this->writeFile = new WriteFileAction($files);
    }

    public function handle(InstallContext $context): void
    {
        $controllers = [
            'SessionController' => 'auth/controllers/SessionController.stub',
            'UserController' => 'auth/controllers/UserController.stub',
            'UserEmailResetNotificationController' => 'auth/controllers/UserEmailResetNotificationController.stub',
            'UserEmailVerificationController' => 'auth/controllers/UserEmailVerificationController.stub',
            'UserEmailVerificationNotificationController' => 'auth/controllers/UserEmailVerificationNotificationController.stub',
            'UserPasswordController' => 'auth/controllers/UserPasswordController.stub',
            'UserProfileController' => 'auth/controllers/UserProfileController.stub',
            'UserTwoFactorAuthenticationController' => 'auth/controllers/UserTwoFactorAuthenticationController.stub',
        ];

        foreach ($controllers as $controller => $stub) {
            $this->writeFile->handle(
                $context,
                'app/Http/Controllers/'.$controller.'.php',
                $this->stubs->render($stub, $this->replacements($context)),
            );
        }
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
