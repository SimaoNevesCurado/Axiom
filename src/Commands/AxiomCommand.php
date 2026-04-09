<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Commands;

use Illuminate\Console\Command;
use SimaoCurado\Axiom\Actions\DetectFrontendStackAction;
use SimaoCurado\Axiom\Actions\InstallAxiomAction;
use SimaoCurado\Axiom\Data\InstallSelections;
use SimaoCurado\Axiom\Enums\AiGuidelinePreset;
use SimaoCurado\Axiom\Enums\AuthScaffoldPreset;
use SimaoCurado\Axiom\Enums\DebugToolPreset;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

final class AxiomCommand extends Command
{
    protected $signature = 'axiom:install
        {--ai= : AI guideline preset (boost, codex, claude, none)}
        {--auth-routes= : Authentication scaffold preset (fortify, app-managed)}
        {--skills : Install Axiom AI skills into .ai/skills}
        {--ssr : Use SSR in frontend starter kits}
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
        {--no-composer-update : Skip automatic composer update after install}
        {--force : Overwrite existing files}';

    protected $description = 'Install opinionated Axiom presets into the host application';

    public function handle(InstallAxiomAction $installAxiom, DetectFrontendStackAction $detectFrontendStack): int
    {
        $this->renderInstallBanner();

        $frontendStack = $detectFrontendStack->handle(base_path())->stack;
        $hasFortifyInstalled = $this->hasFortifyInstalled();

        $installQualityGuidelines = $this->resolveToggle(
            option: 'quality',
            question: 'Install quality presets?',
        );

        $phpTools = $this->resolvePhpTools($installQualityGuidelines);
        $frontendTools = $this->resolveFrontendTools();

        $selections = new InstallSelections(
            aiGuidelines: $this->resolveAiGuidelines(),
            installAiSkills: $this->resolveToggle(
                option: 'skills',
                question: 'Install AI skills?',
            ),
            authScaffold: $this->resolveAuthScaffold($hasFortifyInstalled),
            installSsr: $this->resolveSsr(),
            installArchitectureGuidelines: $this->resolveToggle(
                option: 'actions',
                question: 'Install Actions + Dto folders?',
            ),
            installQualityGuidelines: $installQualityGuidelines,
            installStrictLaravelDefaults: $this->resolveToggle(
                option: 'strict',
                question: 'Install strict Laravel defaults?',
            ),
            installComposerScripts: $this->resolveToggle(
                option: 'scripts',
                question: 'Add composer scripts?',
            ),
            installPhpQualityDependencies: $phpTools['legacy_bundle'],
            installFrontendQualityDependencies: $frontendTools['legacy_bundle'],
            installBunFrontendTooling: $frontendTools['enabled'],
            installPhpStan: $phpTools['phpstan'],
            installRector: $phpTools['rector'],
            installPint: $phpTools['pint'],
            installTypeCoverage: $phpTools['type_coverage'],
            installOxlint: $frontendTools['oxlint'],
            installPrettier: $frontendTools['prettier'],
            installConcurrently: $frontendTools['concurrently'],
            installNpmCheckUpdates: $frontendTools['ncu'],
            debugTool: $this->resolveDebugTool(),
            overwriteFiles: (bool) $this->option('force'),
            frontendStack: $frontendStack,
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
            if (! $this->shouldSkipComposerUpdate()) {
                $this->line('  • Running `composer update`...');
                $this->runComposerUpdate();
            } else {
                $this->line('  • Run `composer update` to sync new PHP dependencies and update composer.lock.');
            }
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

    private function renderInstallBanner(): void
    {
        if (! $this->output->isDecorated()) {
            $this->line('AXIOM');
            $this->newLine();

            return;
        }

        $c208 = "\033[38;5;208m";
        $c214 = "\033[38;5;214m";
        $c220 = "\033[38;5;220m";
        $c129 = "\033[38;5;129m";
        $c93 = "\033[38;5;93m";
        $c57 = "\033[38;5;57m";
        $reset = "\033[0m";

        $this->line($c208.' █████╗ ██╗  ██╗██╗ ██████╗ '.$reset);
        $this->line($c214.'██╔══██╗╚██╗██╔╝██║██╔═══██╗'.$reset);
        $this->line($c220.'███████║ ╚███╔╝ ██║██║   ██║'.$reset);
        $this->line($c129.'██╔══██║ ██╔██╗ ██║██║   ██║'.$reset);
        $this->line($c93.' ██║  ██║██╔╝ ██╗██║╚██████╔╝'.$reset);
        $this->line($c57.' ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝ ╚═════╝ '.$reset);
        $this->newLine();
        $this->line($c93.'A X I O M'.$reset);
        $this->line($c93.'Axiom Installer'.$reset);
        $this->newLine();
    }

    private function shouldSkipComposerUpdate(): bool
    {
        return (bool) $this->option('no-composer-update');
    }

    private function runComposerUpdate(): void
    {
        $process = new Process(['composer', 'update'], base_path());
        $process->setTimeout(null);

        $exitCode = $process->run(function (string $type, string $buffer): void {
            if ($type === Process::OUT) {
                $this->output->write($buffer);

                return;
            }

            $this->output->write("<fg=red>{$buffer}</>");
        });

        if ($exitCode !== 0) {
            $this->components->warn('`composer update` failed. Run it manually to sync dependencies.');
        }
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

    private function resolveSsr(): bool
    {
        if ((bool) $this->option('ssr')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            return false;
        }

        return confirm(
            label: 'Use Server Side Rendering?',
            default: $this->hasSsrEntrypoint(),
            hint: 'If enabled, Axiom keeps SSR wired into the project dev workflow.',
        );
    }

    private function resolveAuthScaffold(bool $hasFortifyInstalled): AuthScaffoldPreset
    {
        $option = $this->option('auth-routes');

        if (is_string($option) && $option !== '') {
            return AuthScaffoldPreset::fromOption($option);
        }

        if (! $hasFortifyInstalled) {
            return AuthScaffoldPreset::AppManaged;
        }

        if (! $this->input->isInteractive()) {
            return AuthScaffoldPreset::Fortify;
        }

        /** @var string $selection */
        $selection = select(
            label: 'Authentication routes',
            options: AuthScaffoldPreset::labels(),
            default: AuthScaffoldPreset::Fortify->value,
        );

        return AuthScaffoldPreset::from($selection);
    }

    private function hasSsrEntrypoint(): bool
    {
        $paths = [
            'resources/js/ssr.js',
            'resources/js/ssr.jsx',
            'resources/js/ssr.ts',
            'resources/js/ssr.tsx',
        ];

        foreach ($paths as $path) {
            if (file_exists(base_path($path))) {
                return true;
            }
        }

        return false;
    }

    private function hasFortifyInstalled(): bool
    {
        $composerPath = base_path('composer.json');

        if (! file_exists($composerPath)) {
            return false;
        }

        /** @var array<string, mixed>|null $composer */
        $composer = json_decode((string) file_get_contents($composerPath), true);

        if (! is_array($composer)) {
            return false;
        }

        return isset($composer['require']['laravel/fortify']);
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

    /**
     * @return array{legacy_bundle: bool, phpstan: bool, rector: bool, pint: bool, type_coverage: bool}
     */
    private function resolvePhpTools(bool $installQualityGuidelines): array
    {
        $legacyBundle = (bool) $this->option('php-deps');

        $resolved = [
            'legacy_bundle' => $legacyBundle,
            'phpstan' => $legacyBundle || (bool) $this->option('phpstan'),
            'rector' => $legacyBundle || (bool) $this->option('rector'),
            'pint' => $legacyBundle || (bool) $this->option('pint'),
            'type_coverage' => $legacyBundle || (bool) $this->option('type-coverage'),
        ];

        if (! $installQualityGuidelines && ! $this->hasExplicitPhpToolSelection()) {
            return [
                'legacy_bundle' => false,
                'phpstan' => false,
                'rector' => false,
                'pint' => false,
                'type_coverage' => false,
            ];
        }

        if ($installQualityGuidelines && $this->input->isInteractive() && ! $this->hasExplicitPhpToolSelection()) {
            /** @var list<string> $selected */
            $selected = multiselect(
                label: 'Choose PHP tools',
                options: [
                    'phpstan' => 'PHPStan + Larastan',
                    'rector' => 'Rector',
                    'pint' => 'Pint',
                    'type_coverage' => 'Pest type coverage',
                ],
                default: ['phpstan', 'rector', 'pint', 'type_coverage'],
                scroll: 8,
                hint: 'Use the space bar to select tools.',
            );

            $resolved['phpstan'] = in_array('phpstan', $selected, true);
            $resolved['rector'] = in_array('rector', $selected, true);
            $resolved['pint'] = in_array('pint', $selected, true);
            $resolved['type_coverage'] = in_array('type_coverage', $selected, true);
            $resolved['legacy_bundle'] = false;
        }

        return $resolved;
    }

    /**
     * @return array{enabled: bool, legacy_bundle: bool, oxlint: bool, prettier: bool, concurrently: bool, ncu: bool}
     */
    private function resolveFrontendTools(): array
    {
        $legacyBundle = (bool) $this->option('frontend-deps');
        $enabled = $legacyBundle
            || (bool) $this->option('oxlint')
            || (bool) $this->option('prettier')
            || (bool) $this->option('concurrently')
            || (bool) $this->option('ncu');

        $resolved = [
            'enabled' => $enabled,
            'legacy_bundle' => $legacyBundle,
            'oxlint' => $legacyBundle || (bool) $this->option('oxlint'),
            'prettier' => $legacyBundle || (bool) $this->option('prettier'),
            'concurrently' => $legacyBundle || (bool) $this->option('concurrently'),
            'ncu' => $legacyBundle || (bool) $this->option('ncu'),
        ];

        if (! file_exists(base_path('package.json'))) {
            return $resolved;
        }

        if ($this->input->isInteractive() && ! $this->hasExplicitFrontendToolSelection()) {
            $enabled = confirm(
                label: 'Install Bun frontend tooling?',
                default: true,
            );

            $resolved['enabled'] = $enabled;

            if (! $enabled) {
                $resolved['legacy_bundle'] = false;
                $resolved['oxlint'] = false;
                $resolved['prettier'] = false;
                $resolved['concurrently'] = false;
                $resolved['ncu'] = false;

                return $resolved;
            }

            /** @var list<string> $selected */
            $selected = multiselect(
                label: 'Choose frontend tools',
                options: [
                    'oxlint' => 'Oxlint',
                    'prettier' => 'Prettier',
                    'concurrently' => 'concurrently',
                    'ncu' => 'npm-check-updates',
                ],
                default: ['oxlint', 'prettier', 'concurrently', 'ncu'],
                scroll: 8,
                hint: 'Use the space bar to select tools.',
            );

            $resolved['oxlint'] = in_array('oxlint', $selected, true);
            $resolved['prettier'] = in_array('prettier', $selected, true);
            $resolved['concurrently'] = in_array('concurrently', $selected, true);
            $resolved['ncu'] = in_array('ncu', $selected, true);
            $resolved['legacy_bundle'] = false;
        }

        return $resolved;
    }

    private function hasExplicitPhpToolSelection(): bool
    {
        return (bool) $this->option('php-deps')
            || (bool) $this->option('phpstan')
            || (bool) $this->option('rector')
            || (bool) $this->option('pint')
            || (bool) $this->option('type-coverage');
    }

    private function hasExplicitFrontendToolSelection(): bool
    {
        return (bool) $this->option('frontend-deps')
            || (bool) $this->option('oxlint')
            || (bool) $this->option('prettier')
            || (bool) $this->option('concurrently')
            || (bool) $this->option('ncu');
    }
}
