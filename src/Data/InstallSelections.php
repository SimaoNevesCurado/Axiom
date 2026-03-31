<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Data;

use SimaoCurado\Axiom\Enums\AiGuidelinePreset;

final readonly class InstallSelections
{
    public function __construct(
        public AiGuidelinePreset $aiGuidelines,
        public bool $installAiSkills,
        public bool $installArchitectureGuidelines,
        public bool $installQualityGuidelines,
        public bool $installStrictLaravelDefaults,
        public bool $installComposerScripts,
        public bool $installPhpQualityDependencies,
        public bool $installFrontendQualityDependencies,
        public bool $overwriteFiles,
    ) {}
}
