<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Enums\AiGuidelinePreset;
use SimaoCurado\Axiom\Enums\FrontendStack;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class PublishAiGuidelinesAction
{
    private DetectFrontendStackAction $detectFrontendStack;

    private ResolveStubPathAction $stubs;

    private WriteFileAction $writeFile;

    public function __construct(Filesystem $files)
    {
        $this->detectFrontendStack = new DetectFrontendStackAction($files);
        $this->stubs = new ResolveStubPathAction($files);
        $this->writeFile = new WriteFileAction($files);
    }

    public function handle(InstallContext $context): void
    {
        foreach ($this->selectedGuidelines($context) as $guideline) {
            $this->writeFile->handle(
                $context,
                $this->guidelinesPath($guideline),
                $this->stubs->contents($this->guidelinesStub($context, $guideline)),
            );
        }
    }

    /**
     * @return list<AiGuidelinePreset>
     */
    private function selectedGuidelines(InstallContext $context): array
    {
        if ($context->selections->aiGuidelinePresets !== []) {
            $normalized = [];

            foreach ($context->selections->aiGuidelinePresets as $preset) {
                if ($preset === AiGuidelinePreset::None) {
                    continue;
                }

                if (! in_array($preset, $normalized, true)) {
                    $normalized[] = $preset;
                }
            }

            return $normalized;
        }

        if ($context->selections->aiGuidelines === AiGuidelinePreset::None) {
            return [];
        }

        return [$context->selections->aiGuidelines];
    }

    private function guidelinesPath(AiGuidelinePreset $preset): string
    {
        return match ($preset) {
            AiGuidelinePreset::Boost, AiGuidelinePreset::Codex => 'AGENTS.md',
            AiGuidelinePreset::Claude => 'CLAUDE.md',
            AiGuidelinePreset::Gemini => 'GEMINI.md',
            AiGuidelinePreset::Opencode => 'OPENCODE.md',
            AiGuidelinePreset::None => 'AGENTS.md',
        };
    }

    private function guidelinesStub(InstallContext $context, AiGuidelinePreset $preset): string
    {
        $frontendStack = $this->detectFrontendStack->handle($context->basePath);

        if ($frontendStack === FrontendStack::Vue) {
            return match ($preset) {
                AiGuidelinePreset::Boost, AiGuidelinePreset::Codex => 'ai/AGENTS.vue.stub',
                AiGuidelinePreset::Claude => 'ai/CLAUDE.vue.stub',
                AiGuidelinePreset::Gemini => 'ai/GEMINI.vue.stub',
                AiGuidelinePreset::Opencode => 'ai/OPENCODE.stub',
                AiGuidelinePreset::None => 'ai/AGENTS.vue.stub',
            };
        }

        if ($frontendStack === FrontendStack::React) {
            return match ($preset) {
                AiGuidelinePreset::Boost, AiGuidelinePreset::Codex => 'ai/AGENTS.react.stub',
                AiGuidelinePreset::Claude => 'ai/CLAUDE.react.stub',
                AiGuidelinePreset::Gemini => 'ai/GEMINI.react.stub',
                AiGuidelinePreset::Opencode => 'ai/OPENCODE.stub',
                AiGuidelinePreset::None => 'ai/AGENTS.react.stub',
            };
        }

        return match ($preset) {
            AiGuidelinePreset::Boost => 'ai/AGENTS.boost.stub',
            AiGuidelinePreset::Codex => 'ai/AGENTS.none.stub',
            AiGuidelinePreset::Claude => 'ai/CLAUDE.none.stub',
            AiGuidelinePreset::Gemini => 'ai/GEMINI.none.stub',
            AiGuidelinePreset::Opencode => 'ai/OPENCODE.stub',
            AiGuidelinePreset::None => 'ai/AGENTS.none.stub',
        };
    }
}
