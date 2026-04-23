<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Data;

use SimaoCurado\Axiom\Enums\AiGuidelinePreset;
use SimaoCurado\Axiom\Enums\AuthRoutesPreset;
use SimaoCurado\Axiom\Enums\DebugToolPreset;

final readonly class InstallSelections
{
    /**
     * @param  list<AiGuidelinePreset>  $aiGuidelinePresets
     * @param  list<string>  $aiSkills
     */
    public function __construct(
        public AiGuidelinePreset $aiGuidelines,
        public bool $installAiSkills,
        public AuthRoutesPreset $authRoutes,
        public bool $installSsr,
        public bool $installArchitectureGuidelines,
        public bool $installQualityGuidelines,
        public bool $installStrictLaravelDefaults,
        public bool $installComposerScripts,
        public bool $installPhpQualityDependencies = false,
        public bool $installFrontendQualityDependencies = false,
        public bool $installBunFrontendTooling = false,
        public bool $installPhpStan = false,
        public bool $installRector = false,
        public bool $installPint = false,
        public bool $installTypeCoverage = false,
        public bool $installOxlint = false,
        public bool $installPrettier = false,
        public bool $installConcurrently = false,
        public bool $installNpmCheckUpdates = false,
        public DebugToolPreset $debugTool = DebugToolPreset::None,
        public bool $overwriteFiles = false,
        public array $aiGuidelinePresets = [],
        public array $aiSkills = [],
        public bool $forceAppRoutes = false,
    ) {}
}
