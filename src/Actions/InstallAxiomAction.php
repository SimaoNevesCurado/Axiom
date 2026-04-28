<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Actions\Auth\InstallAppManagedAuthAction;
use SimaoCurado\Axiom\Data\InstallResult;
use SimaoCurado\Axiom\Data\InstallSelections;
use SimaoCurado\Axiom\Enums\AuthRoutesPreset;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class InstallAxiomAction
{
    private InstallAppManagedAuthAction $installAppManagedAuth;

    private PublishAiGuidelinesAction $publishAiGuidelines;

    private PublishAiSkillsAction $publishAiSkills;

    private PublishArchitectureGuidelinesAction $publishArchitectureGuidelines;

    private PublishQualityPresetFilesAction $publishQualityPresetFiles;

    private PublishStrictLaravelDefaultsAction $publishStrictLaravelDefaults;

    private UpdateComposerDevDependenciesAction $updateComposerDevDependencies;

    private UpdateComposerScriptsAction $updateComposerScripts;

    private UpdatePackageDevDependenciesAction $updatePackageDevDependencies;

    public function __construct(Filesystem $files)
    {
        $this->installAppManagedAuth = new InstallAppManagedAuthAction($files);
        $this->publishAiGuidelines = new PublishAiGuidelinesAction($files);
        $this->publishAiSkills = new PublishAiSkillsAction($files);
        $this->publishArchitectureGuidelines = new PublishArchitectureGuidelinesAction($files);
        $this->publishQualityPresetFiles = new PublishQualityPresetFilesAction($files);
        $this->publishStrictLaravelDefaults = new PublishStrictLaravelDefaultsAction($files);
        $this->updateComposerDevDependencies = new UpdateComposerDevDependenciesAction($files);
        $this->updateComposerScripts = new UpdateComposerScriptsAction($files);
        $this->updatePackageDevDependencies = new UpdatePackageDevDependenciesAction($files);
    }

    public function handle(InstallSelections $selections, string $basePath): InstallResult
    {
        $context = new InstallContext($selections, $basePath);

        $this->publishAiSkills->handle($context);
        $this->updateComposerScripts->handle($context);
        $this->updateComposerDevDependencies->handle($context);
        $this->updatePackageDevDependencies->handle($context);
        $this->publishAiGuidelines->handle($context);
        $this->publishArchitectureGuidelines->handle($context);
        $this->publishQualityPresetFiles->handle($context);
        $this->publishStrictLaravelDefaults->handle($context);

        if ($selections->authRoutes === AuthRoutesPreset::AppManaged && $selections->installAuthScaffold) {
            $this->installAppManagedAuth->handle($context);
        }

        return $context->result();
    }
}
