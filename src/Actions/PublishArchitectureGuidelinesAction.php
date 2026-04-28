<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class PublishArchitectureGuidelinesAction
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
        if (! $context->selections->installArchitectureGuidelines) {
            return;
        }

        $this->writeFile->handle($context, '.ai/architecture.md', $this->stubs->contents('docs/architecture.stub'));
        $this->writeFile->handle($context, 'app/Actions/.gitkeep', '');
        $this->writeFile->handle($context, 'app/Dto/.gitkeep', '');
        $this->writeFile->handle($context, 'app/Enums/.gitkeep', '');
    }
}
