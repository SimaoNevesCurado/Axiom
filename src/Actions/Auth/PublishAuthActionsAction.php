<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Actions\ResolveStubPathAction;
use SimaoCurado\Axiom\Actions\WriteFileAction;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class PublishAuthActionsAction
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
        $actions = [
            'CreateUser' => 'auth/actions/CreateUser.stub',
            'CreateUserEmailResetNotification' => 'auth/actions/CreateUserEmailResetNotification.stub',
            'CreateUserEmailVerificationNotification' => 'auth/actions/CreateUserEmailVerificationNotification.stub',
            'CreateUserPassword' => 'auth/actions/CreateUserPassword.stub',
            'DeleteUser' => 'auth/actions/DeleteUser.stub',
            'UpdateUser' => 'auth/actions/UpdateUser.stub',
            'UpdateUserPassword' => 'auth/actions/UpdateUserPassword.stub',
        ];

        foreach ($actions as $action => $stub) {
            $this->writeFile->handle($context, 'app/Actions/'.$action.'.php', $this->stubs->contents($stub));
        }
    }
}
