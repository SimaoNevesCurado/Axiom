<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Closure;
use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Actions\Auth\InstallAppManagedAuthAction;
use SimaoCurado\Axiom\Data\InstallResult;
use SimaoCurado\Axiom\Data\InstallSelections;
use SimaoCurado\Axiom\Data\InstallStep;
use SimaoCurado\Axiom\Enums\AuthRoutesPreset;
use SimaoCurado\Axiom\Exceptions\InstallStepFailedException;
use SimaoCurado\Axiom\Support\InstallContext;
use Throwable;

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

    /**
     * @param  (callable(string, Closure(): void): void)|null  $runStep
     */
    public function handle(InstallSelections $selections, string $basePath, ?callable $runStep = null): InstallResult
    {
        $context = new InstallContext($selections, $basePath);

        foreach ($this->steps($selections) as $step) {
            $this->runStep($step, $context, $runStep);
        }

        return $context->result();
    }

    /**
     * @param  (callable(string, Closure(): void): void)|null  $runStep
     */
    private function runStep(InstallStep $step, InstallContext $context, ?callable $runStep): void
    {
        $run = fn (): null => $step->run($context);
        $writtenBefore = count($context->written);
        $skippedBefore = count($context->skipped);
        $plannedBefore = count($context->planned);
        $previousStep = $context->currentStep;
        $context->currentStep = $step->label;

        try {
            if ($runStep !== null) {
                $runStep($step->label, $run);
            } else {
                $run();
            }
        } catch (Throwable $exception) {
            throw new InstallStepFailedException($step->label, $exception);
        } finally {
            $context->currentStep = $previousStep;
        }

        $context->recordStepResult(
            $step->label,
            count($context->written) - $writtenBefore,
            count($context->skipped) - $skippedBefore,
            count($context->planned) - $plannedBefore,
        );
    }

    /**
     * @return list<InstallStep>
     */
    private function steps(InstallSelections $selections): array
    {
        $steps = [
            new InstallStep('Publishing AI skills', fn (InstallContext $context): null => $this->publishAiSkills->handle($context)),
            new InstallStep('Updating composer scripts', fn (InstallContext $context): null => $this->updateComposerScripts->handle($context)),
            new InstallStep('Updating Composer dev dependencies', fn (InstallContext $context): null => $this->updateComposerDevDependencies->handle($context)),
            new InstallStep('Updating frontend dev dependencies', fn (InstallContext $context): null => $this->updatePackageDevDependencies->handle($context)),
            new InstallStep('Publishing AI guidelines', fn (InstallContext $context): null => $this->publishAiGuidelines->handle($context)),
            new InstallStep('Publishing architecture guidelines', fn (InstallContext $context): null => $this->publishArchitectureGuidelines->handle($context)),
            new InstallStep('Publishing quality presets', fn (InstallContext $context): null => $this->publishQualityPresetFiles->handle($context)),
            new InstallStep('Publishing strict Laravel defaults', fn (InstallContext $context): null => $this->publishStrictLaravelDefaults->handle($context)),
        ];

        if ($selections->authRoutes === AuthRoutesPreset::AppManaged && $selections->installAuthScaffold) {
            $steps[] = new InstallStep('Installing app-managed auth scaffold', fn (InstallContext $context): null => $this->installAppManagedAuth->handle($context));
        }

        return $steps;
    }
}
