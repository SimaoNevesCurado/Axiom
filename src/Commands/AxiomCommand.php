<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Commands;

use Illuminate\Console\Command;
use SimaoCurado\Axiom\Actions\InstallAxiomAction;
use SimaoCurado\Axiom\Data\InstallSelections;
use SimaoCurado\Axiom\Enums\AiGuidelinePreset;
use SimaoCurado\Axiom\Enums\DebugToolPreset;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

final class AxiomCommand extends Command
{
    protected $signature = 'axiom:install
        {--ai= : AI guideline preset (boost, codex, claude, none)}
        {--skills : Install Axiom AI skills into .ai/skills}
        {--actions : Install action-oriented architecture guidance}
        {--quality : Install quality and tooling guidance}
        {--strict : Install strict Laravel defaults config}
        {--scripts : Install opinionated composer scripts into the host project}
        {--php-deps : Add recommended PHP quality dev dependencies to composer.json}
        {--frontend-deps : Add recommended frontend quality dev dependencies to package.json}
        {--phpstan : Add PHPStan and Larastan to composer.json}
        {--rector : Add Rector to composer.json}
        {--pint : Add Laravel Pint to composer.json}
        {--type-coverage : Add Pest type coverage to composer.json}
        {--debug-tool= : Debug tool preset (none, debugbar, telescope)}
        {--oxlint : Add Oxlint to package.json}
        {--prettier : Add Prettier and plugins to package.json}
        {--concurrently : Add concurrently to package.json}
        {--ncu : Add npm-check-updates to package.json}
        {--force : Overwrite existing files}';

    protected $description = 'Install opinionated Axiom presets into the host application';

    public function handle(InstallAxiomAction $installAxiom): int
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
            installPhpStan: $this->resolveToggle(
                option: 'phpstan',
                question: 'Add PHPStan?',
            ),
            installRector: $this->resolveToggle(
                option: 'rector',
                question: 'Add Rector?',
            ),
            installPint: $this->resolveToggle(
                option: 'pint',
                question: 'Add Pint?',
            ),
            installTypeCoverage: $this->resolveToggle(
                option: 'type-coverage',
                question: 'Add Pest type coverage?',
            ),
            installOxlint: $this->resolveFrontendToggle(
                option: 'oxlint',
                question: 'Add Oxlint?',
            ),
            installPrettier: $this->resolveFrontendToggle(
                option: 'prettier',
                question: 'Add Prettier?',
            ),
            installConcurrently: $this->resolveFrontendToggle(
                option: 'concurrently',
                question: 'Add concurrently?',
            ),
            installNpmCheckUpdates: $this->resolveFrontendToggle(
                option: 'ncu',
                question: 'Add npm-check-updates?',
            ),
            debugTool: $this->resolveDebugTool(),
            overwriteFiles: (bool) $this->option('force'),
        );

        $result = $installAxiom->handle($selections, base_path());

        $this->newLine();
        $this->components->info('Axiom installer finished.');

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

        if (in_array('composer.json', $result->written, true)) {
            $this->line('  • Run `composer update` to sync new PHP dependencies and update composer.lock.');
        } else {
            $this->line('  • Run `composer install` if you still need to install PHP dependencies.');
        }

        if (in_array('package.json', $result->written, true)) {
            $this->line('  • Run `bun install` to sync new frontend dependencies.');
        } elseif (file_exists(base_path('package.json'))) {
            $this->line('  • Run `bun install` if you still need to install frontend dependencies.');
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

    private function resolveFrontendToggle(string $option, string $question): bool
    {
        if (! file_exists(base_path('package.json'))) {
            return false;
        }

        return $this->resolveToggle($option, $question);
    }

    private function resolveDebugTool(): DebugToolPreset
    {
        $option = $this->option('debug-tool');

        if (is_string($option) && $option !== '') {
            return DebugToolPreset::from($option);
        }

        if (! $this->input->isInteractive()) {
            return DebugToolPreset::None;
        }

        /** @var string $selection */
        $selection = select(
            label: 'Choose a debug tool',
            options: DebugToolPreset::labels(),
            default: DebugToolPreset::None->value,
        );

        return DebugToolPreset::from($selection);
    }
}
