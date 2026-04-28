<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class PublishStrictLaravelDefaultsAction
{
    private RegisterBootstrapProviderAction $registerProvider;

    private ResolveStubPathAction $stubs;

    private WriteFileAction $writeFile;

    public function __construct(Filesystem $files)
    {
        $this->registerProvider = new RegisterBootstrapProviderAction($files);
        $this->stubs = new ResolveStubPathAction($files);
        $this->writeFile = new WriteFileAction($files);
    }

    public function handle(InstallContext $context): void
    {
        if (! $context->selections->installStrictLaravelDefaults) {
            return;
        }

        $this->writeFile->handle($context, 'config/axiom.php', $this->stubs->contents('config/axiom.stub'));
        $this->writeFile->handle($context, 'app/Providers/AxiomServiceProvider.php', $this->stubs->contents('providers/AxiomServiceProvider.stub'));

        $this->registerProvider->handle($context, 'App\\Providers\\AxiomServiceProvider::class');
    }
}
