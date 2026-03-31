<?php

declare(strict_types=1);

namespace SimaoCurado\LaravelExtra\Commands;

use Illuminate\Console\Command;
use SimaoCurado\LaravelExtra\Actions\InstallLaravelExtraAction;
use SimaoCurado\LaravelExtra\Data\InstallSelections;
use SimaoCurado\LaravelExtra\Enums\AiGuidelinePreset;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

final class LaravelExtraCommand extends Command
{
    protected $signature = 'laravel-extra:install
        {--ai= : AI guideline preset (boost, codex, claude, none)}
        {--skills : Install Laravel Extra AI skills into .ai/skills}
        {--actions : Install action-oriented architecture guidance}
        {--quality : Install quality and tooling guidance}
        {--strict : Install strict Laravel defaults config}
        {--scripts : Install opinionated composer scripts into the host project}
        {--php-deps : Add recommended PHP quality dev dependencies to composer.json}
        {--frontend-deps : Add recommended frontend quality dev dependencies to package.json}
        {--force : Overwrite existing files}';

    protected $description = 'Install opinionated Laravel Extra presets into the host application';

    public function handle(InstallLaravelExtraAction $installLaravelExtra): int
    {
        $selections = new InstallSelections(
            aiGuidelines: $this->resolveAiGuidelines(),
            installAiSkills: $this->resolveToggle(
                option: 'skills',
                question: 'Do you want AI skills for Laravel Extra workflows?',
            ),
            installArchitectureGuidelines: $this->resolveToggle(
                option: 'actions',
                question: 'Do you want action-oriented architecture guidelines and starter folders?',
            ),
            installQualityGuidelines: $this->resolveToggle(
                option: 'quality',
                question: 'Do you want quality tooling guidelines for PHPStan, Rector, Pint, and Oxlint?',
            ),
            installStrictLaravelDefaults: $this->resolveToggle(
                option: 'strict',
                question: 'Do you want strict Laravel defaults config for this project?',
            ),
            installComposerScripts: $this->resolveToggle(
                option: 'scripts',
                question: 'Do you want me to add the recommended composer scripts to this project?',
            ),
            installPhpQualityDependencies: $this->resolveToggle(
                option: 'php-deps',
                question: 'Do you want me to add recommended PHP quality dependencies to composer.json?',
            ),
            installFrontendQualityDependencies: $this->resolveToggle(
                option: 'frontend-deps',
                question: 'Do you want me to add recommended frontend quality dependencies to package.json when available?',
            ),
            overwriteFiles: (bool) $this->option('force'),
        );

        $result = $installLaravelExtra->handle($selections, base_path());

        $this->components->info('Laravel Extra installer finished.');

        foreach ($result->written as $path) {
            $this->line("  <fg=green>Wrote</> {$path}");
        }

        foreach ($result->skipped as $path) {
            $this->line("  <fg=yellow>Skipped</> {$path}");
        }

        if ($result->written === [] && $result->skipped === []) {
            $this->line('  Nothing to install.');
        }

        return self::SUCCESS;
    }

    private function resolveAiGuidelines(): AiGuidelinePreset
    {
        $option = $this->option('ai');

        if (is_string($option) && $option !== '') {
            return AiGuidelinePreset::from($option);
        }

        if (! $this->input->isInteractive()) {
            return AiGuidelinePreset::None;
        }

        /** @var string $selection */
        $selection = select(
            label: 'Which AI guidelines preset do you want to install?',
            options: AiGuidelinePreset::labels(),
            default: AiGuidelinePreset::Boost->value,
        );

        return AiGuidelinePreset::from($selection);
    }

    private function resolveToggle(string $option, string $question): bool
    {
        if ((bool) $this->option($option)) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            return false;
        }

        return confirm(
            label: $question,
            default: true,
        );
    }
}
