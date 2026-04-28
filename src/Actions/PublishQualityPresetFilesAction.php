<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class PublishQualityPresetFilesAction
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
        if (! $context->selections->installQualityGuidelines) {
            return;
        }

        $files = [
            '.ai/quality.md' => 'docs/quality.stub',
            'phpstan.neon' => 'quality/phpstan.neon.stub',
            'rector.php' => 'quality/rector.php.stub',
            'pint.json' => 'quality/pint.json.stub',
            'tests/Unit/ArchTest.php' => 'quality/ArchTest.php.stub',
        ];

        foreach ($files as $path => $stub) {
            $this->writeFile->handle($context, $path, $this->stubs->contents($stub));
        }
    }
}
