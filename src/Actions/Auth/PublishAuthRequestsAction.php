<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Actions\ResolveStubPathAction;
use SimaoCurado\Axiom\Actions\WriteFileAction;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class PublishAuthRequestsAction
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
        $requests = [
            'CreateSessionRequest' => 'auth/requests/CreateSessionRequest.stub',
            'CreateUserEmailResetNotificationRequest' => 'auth/requests/CreateUserEmailResetNotificationRequest.stub',
            'CreateUserPasswordRequest' => 'auth/requests/CreateUserPasswordRequest.stub',
            'CreateUserRequest' => 'auth/requests/CreateUserRequest.stub',
            'DeleteUserRequest' => 'auth/requests/DeleteUserRequest.stub',
            'ShowUserTwoFactorAuthenticationRequest' => 'auth/requests/ShowUserTwoFactorAuthenticationRequest.stub',
            'UpdateEmailVerificationRequest' => 'auth/requests/UpdateEmailVerificationRequest.stub',
            'UpdateUserPasswordRequest' => 'auth/requests/UpdateUserPasswordRequest.stub',
            'UpdateUserRequest' => 'auth/requests/UpdateUserRequest.stub',
        ];

        foreach ($requests as $request => $stub) {
            $this->writeFile->handle($context, 'app/Http/Requests/'.$request.'.php', $this->stubs->contents($stub));
        }
    }
}
