<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Actions\ResolveStubPathAction;
use SimaoCurado\Axiom\Actions\WriteFileAction;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class PublishAuthRulesAction
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
        $this->writeFile->handle($context, 'app/Rules/ValidEmail.php', $this->stubs->contents('auth/rules/ValidEmail.stub'));
    }
}
