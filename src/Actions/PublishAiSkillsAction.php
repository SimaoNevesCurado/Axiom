<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class PublishAiSkillsAction
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
        if (! $context->selections->installAiSkills) {
            return;
        }

        $skills = [
            'actions' => 'skills/actions.stub',
            'dto' => 'skills/dto.stub',
            'enum' => 'skills/enum.stub',
            'crud' => 'skills/crud.stub',
            'quality' => 'skills/quality.stub',
        ];

        foreach ($skills as $name => $stub) {
            if ($context->selections->aiSkills !== [] && ! in_array($name, $context->selections->aiSkills, true)) {
                continue;
            }

            $this->writeFile->handle($context, '.ai/skills/'.$name.'.md', $this->stubs->contents($stub));
        }
    }
}
