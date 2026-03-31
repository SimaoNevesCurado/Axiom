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
                question: 'Install AI skills?',
            ),
            installArchitectureGuidelines: $this->resolveToggle(
                option: 'actions',
                question: 'Install Actions + Dto folders?',
            ),
            installQualityGuidelines: $this->resolveToggle(
                option: 'quality',
                question: 'Install quality presets?',
            ),
            installStrictLaravelDefaults: $this->resolveToggle(
                option: 'strict',
                question: 'Install strict Laravel defaults?',
            ),
            installComposerScripts: $this->resolveToggle(
                option: 'scripts',
                question: 'Add composer scripts?',
            ),
            installPhpQualityDependencies: $this->resolveToggle(
                option: 'php-deps',
                question: 'Add PHP quality dependencies?',
            ),
            installFrontendQualityDependencies: $this->resolveToggle(
                option: 'frontend-deps',
                question: 'Add frontend quality dependencies?',
            ),
            overwriteFiles: (bool) $this->option('force'),
        );

        $result = $installLaravelExtra->handle($selections, base_path());

        $this->newLine();
        $this->components->info('Laravel Extra installer finished.');

        if ($result->written !== []) {
            $this->line('  Created or updated:');

            foreach ($result->written as $path) {
                $this->line("  <fg=green>•</> {$path}");
            }
        }

        if ($result->skipped !== []) {
            $this->line('  Skipped:');

            foreach ($result->skipped as $path) {
                $this->line("  <fg=yellow>•</> {$path}");
            }
        }

        if ($result->written === [] && $result->skipped === []) {
            $this->line('  Nothing to install.');
        }

        $this->newLine();
        $this->line('  Next steps:');
        $this->line('  • Run `composer install` to install PHP dependencies.');

        if (file_exists(base_path('package.json'))) {
            $this->line('  • Run `bun install` to install frontend dependencies.');
        }

        $this->line('  • Review `AGENTS.md` and `.ai/skills/*` if you installed AI guidance.');

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
            label: 'Choose an AI preset',
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
