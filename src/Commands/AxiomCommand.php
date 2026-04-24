<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Commands;

use Illuminate\Console\Command;
use SimaoCurado\Axiom\Actions\InstallAxiomAction;
use SimaoCurado\Axiom\Data\InstallSelections;
use SimaoCurado\Axiom\Enums\AiGuidelinePreset;
use SimaoCurado\Axiom\Enums\AuthRoutesPreset;
use SimaoCurado\Axiom\Enums\DebugToolPreset;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

final class AxiomCommand extends Command
{
    protected $signature = 'axiom:install
        {--ai= : AI guideline preset (boost, codex, claude, gemini, opencode, none)}
        {--skills : Install Axiom AI skills into .ai/skills}
        {--fortify : [Deprecated] Use Fortify routes when laravel/fortify exists}
        {--auth-routes= : Auth routes mode (app, fortify)}
        {--install-auth : Install Axiom auth scaffold when the project has no auth scaffold}
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
        {--force : Overwrite existing files}';

    protected $description = 'Install opinionated Axiom presets into the host application';

    public function handle(InstallAxiomAction $installAxiom): int
    {
        $this->renderBanner();

        $aiGuidelinePresets = $this->resolveAiGuidelinePresets();
        $aiSkills = $this->resolveAiSkills();

        $installQualityGuidelines = $this->resolveToggle(
            option: 'quality',
            question: 'Install quality presets?',
        );

        $phpTools = $this->resolvePhpTools($installQualityGuidelines);
        $frontendTools = $this->resolveFrontendTools();

        $authRoutes = $this->resolveAuthRoutes();

        $selections = new InstallSelections(
            aiGuidelines: $aiGuidelinePresets[0] ?? AiGuidelinePreset::None,
            installAiSkills: $aiSkills !== [],
            authRoutes: $authRoutes,
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
                question: 'Add usefull composer commands',
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
            aiGuidelinePresets: $aiGuidelinePresets,
            aiSkills: $aiSkills,
            installAuthScaffold: $this->resolveInstallAuthScaffold($authRoutes),
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

        $this->line('  • Review `AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `OPENCODE.md`, and `.ai/skills/*` if you installed AI guidance.');

        return self::SUCCESS;
    }

    private function renderBanner(): void
    {
        if (! $this->input->isInteractive()) {
            return;
        }

        $this->line("\033[34m");
        $this->line(' █████╗ ██╗  ██╗██╗ ██████╗ ███╗   ███╗');
        $this->line('██╔══██╗╚██╗██╔╝██║██╔═══██╗████╗ ████║');
        $this->line('███████║ ╚███╔╝ ██║██║   ██║██╔████╔██║');
        $this->line('██╔══██║ ██╔██╗ ██║██║   ██║██║╚██╔╝██║');
        $this->line('██║  ██║██╔╝ ██╗██║╚██████╔╝██║ ╚═╝ ██║');
        $this->line('╚═╝  ╚═╝╚═╝  ╚═╝╚═╝ ╚═════╝ ╚═╝     ╚═╝');
        $this->line("\033[0m");
    }

    /**
     * @return list<AiGuidelinePreset>
     */
    private function resolveAiGuidelinePresets(): array
    {
        $option = $this->option('ai');

        if (is_string($option) && $option !== '') {
            return $this->parseAiPresetsOption($option);
        }

        if (! $this->input->isInteractive()) {
            return [];
        }

        $installAiPresets = confirm(
            label: 'Install AI presets?',
            default: true,
        );

        if (! $installAiPresets) {
            return [];
        }

        /** @var list<string> $selection */
        $selection = multiselect(
            label: 'Choose an AI preset',
            options: [
                AiGuidelinePreset::Boost->value => 'Boost preset (AGENTS.md)',
                AiGuidelinePreset::Codex->value => 'Codex preset (AGENTS.md)',
                AiGuidelinePreset::Claude->value => 'Claude preset (CLAUDE.md)',
                AiGuidelinePreset::Gemini->value => 'Gemini preset (GEMINI.md)',
                AiGuidelinePreset::Opencode->value => 'Opencode preset (OPENCODE.md)',
            ],
            default: [AiGuidelinePreset::Boost->value],
            scroll: 6,
            hint: 'Use the space bar to select one or more presets.',
        );

        if ($selection === []) {
            return [];
        }

        $presets = array_map(
            static fn (string $value): AiGuidelinePreset => AiGuidelinePreset::from($value),
            $selection,
        );

        $unique = [];

        foreach ($presets as $preset) {
            if (! in_array($preset, $unique, true)) {
                $unique[] = $preset;
            }
        }

        return $unique;
    }

    /**
     * @return list<string>
     */
    private function resolveAiSkills(): array
    {
        if ((bool) $this->option('skills')) {
            return ['actions', 'dto', 'enum', 'crud', 'quality'];
        }

        if (! $this->input->isInteractive()) {
            return [];
        }

        $installAiSkills = confirm(
            label: 'Install AI skills?',
            default: true,
        );

        if (! $installAiSkills) {
            return [];
        }

        return ['actions', 'dto', 'enum', 'crud', 'quality'];
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

    private function resolveAuthRoutes(): AuthRoutesPreset
    {
        $fortifyInstalled = $this->hasFortifyInstalled();
        $option = $this->option('auth-routes');

        if (is_string($option) && $option !== '') {
            $preset = AuthRoutesPreset::from($option);

            if ($preset === AuthRoutesPreset::Fortify && ! $fortifyInstalled) {
                $this->components->warn('Skipping Fortify routes because laravel/fortify is not present in composer.json.');

                return AuthRoutesPreset::AppManaged;
            }

            return $preset;
        }

        if ((bool) $this->option('fortify')) {
            if (! $fortifyInstalled) {
                $this->components->warn('Skipping Fortify routes because laravel/fortify is not present in composer.json.');

                return AuthRoutesPreset::AppManaged;
            }

            return AuthRoutesPreset::Fortify;
        }

        if (! $this->input->isInteractive()) {
            return AuthRoutesPreset::AppManaged;
        }

        if (! $fortifyInstalled) {
            return AuthRoutesPreset::AppManaged;
        }

        /** @var string $selection */
        $selection = select(
            label: 'Choose auth routes mode',
            options: AuthRoutesPreset::labels(),
            default: $this->hasFortifyProviderRegistered()
                ? AuthRoutesPreset::Fortify->value
                : AuthRoutesPreset::AppManaged->value,
            hint: 'Fortify mode uses package routes. App managed mode keeps auth routes in routes/web.php.',
        );

        return AuthRoutesPreset::from($selection);
    }

    private function resolveInstallAuthScaffold(AuthRoutesPreset $authRoutes): bool
    {
        if ($authRoutes !== AuthRoutesPreset::AppManaged) {
            return false;
        }

        if ((bool) $this->option('install-auth')) {
            return true;
        }

        if ($this->hasAuthScaffold()) {
            return false;
        }

        if (! $this->input->isInteractive()) {
            return false;
        }

        return confirm(
            label: 'No auth scaffold found. Install Axiom auth scaffold?',
            default: true,
            hint: 'This creates auth controller files and app-managed auth routes in routes/web.php.',
        );
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

    private function hasFortifyProviderRegistered(): bool
    {
        $providersPath = base_path('bootstrap/providers.php');

        if (! file_exists($providersPath)) {
            return false;
        }

        return str_contains(
            (string) file_get_contents($providersPath),
            'App\\Providers\\FortifyServiceProvider::class',
        );
    }

    private function hasAuthScaffold(): bool
    {
        $paths = [
            base_path('routes/auth.php'),
            base_path('config/fortify.php'),
            base_path('app/Providers/FortifyServiceProvider.php'),
            base_path('app/Http/Controllers/SessionController.php'),
            base_path('app/Http/Controllers/UserController.php'),
            base_path('resources/js/pages/auth'),
            base_path('resources/js/pages/session'),
            base_path('resources/js/Pages/Auth'),
            base_path('resources/js/Pages/Session'),
        ];

        foreach ($paths as $path) {
            if (is_dir($path) || file_exists($path)) {
                return true;
            }
        }

        $webRoutesPath = base_path('routes/web.php');

        if (! file_exists($webRoutesPath)) {
            return false;
        }

        $webRoutes = (string) file_get_contents($webRoutesPath);

        return str_contains($webRoutes, "->name('login')")
            || str_contains($webRoutes, "->name('login.store')")
            || str_contains($webRoutes, "->name('register')")
            || str_contains($webRoutes, "->name('password.request')");
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

            $resolved['legacy_bundle'] = true;
            $resolved['oxlint'] = true;
            $resolved['prettier'] = true;
            $resolved['concurrently'] = true;
            $resolved['ncu'] = true;
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

    /**
     * @return list<AiGuidelinePreset>
     */
    private function parseAiPresetsOption(string $option): array
    {
        $values = array_values(
            array_filter(
                array_map(static fn (string $value): string => trim($value), explode(',', $option)),
                static fn (string $value): bool => $value !== '',
            ),
        );

        if ($values === []) {
            return [];
        }

        $presets = array_map(
            static fn (string $value): AiGuidelinePreset => AiGuidelinePreset::from($value),
            $values,
        );

        $filtered = array_values(
            array_filter(
                $presets,
                static fn (AiGuidelinePreset $preset): bool => $preset !== AiGuidelinePreset::None,
            ),
        );

        $unique = [];

        foreach ($filtered as $preset) {
            if (! in_array($preset, $unique, true)) {
                $unique[] = $preset;
            }
        }

        return $unique;
    }
}
