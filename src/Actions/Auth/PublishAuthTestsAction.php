<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Actions\ResolveStubPathAction;
use SimaoCurado\Axiom\Actions\WriteFileAction;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class PublishAuthTestsAction
{
    private ResolveStubPathAction $stubs;

    private WriteFileAction $writeFile;

    public function __construct(Filesystem $files)
    {
        $this->stubs = new ResolveStubPathAction($files);
        $this->writeFile = new WriteFileAction($files);
    }

    public function handle(InstallContext $context): void
    {
        foreach ($this->testStubs() as $target => $stub) {
            $this->writeFile->handle($context, $target, $this->stubs->contents($stub));
        }
    }

    /**
     * @return array<string, string>
     */
    private function testStubs(): array
    {
        return [
            'tests/Feature/Controllers/SessionControllerTest.php' => 'auth/tests/Feature/Controllers/SessionControllerTest.php.stub',
            'tests/Feature/Controllers/UserControllerTest.php' => 'auth/tests/Feature/Controllers/UserControllerTest.php.stub',
            'tests/Feature/Controllers/UserEmailResetNotificationTest.php' => 'auth/tests/Feature/Controllers/UserEmailResetNotificationTest.php.stub',
            'tests/Feature/Controllers/UserEmailVerificationNotificationControllerTest.php' => 'auth/tests/Feature/Controllers/UserEmailVerificationNotificationControllerTest.php.stub',
            'tests/Feature/Controllers/UserEmailVerificationTest.php' => 'auth/tests/Feature/Controllers/UserEmailVerificationTest.php.stub',
            'tests/Feature/Controllers/UserPasswordControllerTest.php' => 'auth/tests/Feature/Controllers/UserPasswordControllerTest.php.stub',
            'tests/Feature/Controllers/UserProfileControllerTest.php' => 'auth/tests/Feature/Controllers/UserProfileControllerTest.php.stub',
            'tests/Feature/Controllers/UserTwoFactorAuthenticationControllerTest.php' => 'auth/tests/Feature/Controllers/UserTwoFactorAuthenticationControllerTest.php.stub',
            'tests/Unit/Actions/CreateUserEmailResetNotificationTest.php' => 'auth/tests/Unit/Actions/CreateUserEmailResetNotificationTest.php.stub',
            'tests/Unit/Actions/CreateUserEmailVerificationNotificationTest.php' => 'auth/tests/Unit/Actions/CreateUserEmailVerificationNotificationTest.php.stub',
            'tests/Unit/Actions/CreateUserPasswordTest.php' => 'auth/tests/Unit/Actions/CreateUserPasswordTest.php.stub',
            'tests/Unit/Actions/CreateUserTest.php' => 'auth/tests/Unit/Actions/CreateUserTest.php.stub',
            'tests/Unit/Actions/DeleteUserTest.php' => 'auth/tests/Unit/Actions/DeleteUserTest.php.stub',
            'tests/Unit/Actions/UpdateUserPasswordTest.php' => 'auth/tests/Unit/Actions/UpdateUserPasswordTest.php.stub',
            'tests/Unit/Actions/UpdateUserTest.php' => 'auth/tests/Unit/Actions/UpdateUserTest.php.stub',
            'tests/Unit/Rules/ValidEmailTest.php' => 'auth/tests/Unit/Rules/ValidEmailTest.php.stub',
        ];
    }
}
