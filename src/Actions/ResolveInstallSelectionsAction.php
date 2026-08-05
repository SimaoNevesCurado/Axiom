<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use BackedEnum;
use InvalidArgumentException;
use SimaoCurado\Axiom\Data\InstallOptions;
use SimaoCurado\Axiom\Data\InstallSelections;
use SimaoCurado\Axiom\Enums\AiGuidelinePreset;
use SimaoCurado\Axiom\Enums\AuthRoutesPreset;
use SimaoCurado\Axiom\Enums\DebugToolPreset;
use SimaoCurado\Axiom\Support\ProjectDetector;
use ValueError;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

final readonly class ResolveInstallSelectionsAction
{
    /**
     * @param  callable(string): void  $warn
     */
    public function handle(InstallOptions $options, ProjectDetector $project, callable $warn): InstallSelections
    {
        $aiGuidelinePresets = $this->resolveAiGuidelinePresets($options);
        $aiSkills = $this->resolveAiSkills($options);

        $installQualityGuidelines = $this->resolveToggle(
            options: $options,
            option: $options->quality,
            question: 'Install quality presets?',
        );

        $phpTools = $this->resolvePhpTools($options, $installQualityGuidelines);
        $frontendTools = $this->resolveFrontendTools($options, $project);
        $authRoutes = $this->resolveAuthRoutes($options, $project, $warn);

        return new InstallSelections(
            aiGuidelines: $aiGuidelinePresets[0] ?? AiGuidelinePreset::None,
            installAiSkills: $aiSkills !== [],
            authRoutes: $authRoutes,
            installSsr: $this->resolveSsr($options, $project),
            installArchitectureGuidelines: $this->resolveToggle(
                options: $options,
                option: $options->actions,
                question: "Install Actions, Enums and DTO's folders?",
            ),
            installQualityGuidelines: $installQualityGuidelines,
            installStrictLaravelDefaults: $this->resolveToggle(
                options: $options,
                option: $options->strict,
                question: 'Install strict Laravel defaults?',
            ),
            installComposerScripts: $this->resolveToggle(
                options: $options,
                option: $options->scripts,
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
            debugTool: $this->resolveDebugTool($options),
            overwriteFiles: $options->force,
            dryRun: $options->dryRun,
            aiGuidelinePresets: $aiGuidelinePresets,
            aiSkills: $aiSkills,
            installAuthScaffold: $this->resolveInstallAuthScaffold($options, $project, $authRoutes),
        );
    }

    /**
     * @return list<AiGuidelinePreset>
     */
    private function resolveAiGuidelinePresets(InstallOptions $options): array
    {
        if ($options->ai !== null) {
            return $this->parseAiPresetsOption($options->ai);
        }

        if (! $options->interactive) {
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

        return $this->unique($presets);
    }

    /**
     * @return list<string>
     */
    private function resolveAiSkills(InstallOptions $options): array
    {
        if ($options->skills) {
            return ['actions', 'dto', 'enum', 'crud', 'quality'];
        }

        if (! $options->interactive) {
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

    private function resolveToggle(InstallOptions $options, bool $option, string $question): bool
    {
        if ($option) {
            return true;
        }

        if (! $options->interactive) {
            return false;
        }

        return confirm(
            label: $question,
            default: true,
        );
    }

    private function resolveSsr(InstallOptions $options, ProjectDetector $project): bool
    {
        if ($options->ssr) {
            return true;
        }

        if (! $options->interactive) {
            return false;
        }

        return confirm(
            label: 'Use Server Side Rendering?',
            default: $project->hasSsrEntrypoint(),
            hint: 'If enabled, Axiom keeps SSR wired into the project dev workflow.',
        );
    }

    /**
     * @param  callable(string): void  $warn
     */
    private function resolveAuthRoutes(InstallOptions $options, ProjectDetector $project, callable $warn): AuthRoutesPreset
    {
        $fortifyInstalled = $project->hasFortifyInstalled();

        if ($options->authRoutes !== null) {
            $preset = $this->parseEnumOption('auth-routes', $options->authRoutes, AuthRoutesPreset::class);

            if ($preset === AuthRoutesPreset::Fortify && ! $fortifyInstalled) {
                $warn('Skipping Fortify routes because laravel/fortify is not present in composer.json.');

                return AuthRoutesPreset::AppManaged;
            }

            return $preset;
        }

        if ($options->fortify) {
            if (! $fortifyInstalled) {
                $warn('Skipping Fortify routes because laravel/fortify is not present in composer.json.');

                return AuthRoutesPreset::AppManaged;
            }

            return AuthRoutesPreset::Fortify;
        }

        if (! $options->interactive || ! $fortifyInstalled) {
            return AuthRoutesPreset::AppManaged;
        }

        /** @var string $selection */
        $selection = select(
            label: 'Choose auth routes mode',
            options: AuthRoutesPreset::labels(),
            default: $project->hasFortifyProviderRegistered()
                ? AuthRoutesPreset::Fortify->value
                : AuthRoutesPreset::AppManaged->value,
            hint: 'Fortify mode uses package routes. App managed mode keeps auth routes in routes/web.php.',
        );

        return AuthRoutesPreset::from($selection);
    }

    private function resolveInstallAuthScaffold(InstallOptions $options, ProjectDetector $project, AuthRoutesPreset $authRoutes): bool
    {
        if ($authRoutes !== AuthRoutesPreset::AppManaged) {
            return false;
        }

        if ($options->installAuth) {
            return true;
        }

        if ($project->hasAppManagedAuthRoutes()) {
            return false;
        }

        $explicitAppManagedRoutes = $options->authRoutes === AuthRoutesPreset::AppManaged->value;

        if ($project->hasFortifyInstalled() && ($explicitAppManagedRoutes || $options->interactive)) {
            return true;
        }

        if ($project->hasAuthScaffold()) {
            return false;
        }

        if (! $options->interactive) {
            return false;
        }

        return confirm(
            label: 'No auth scaffold found. Install Axiom auth scaffold?',
            default: true,
            hint: 'This creates auth controller files and app-managed auth routes in routes/web.php.',
        );
    }

    private function resolveDebugTool(InstallOptions $options): DebugToolPreset
    {
        if ($options->debugTool !== null) {
            return $this->parseEnumOption('debug-tool', $options->debugTool, DebugToolPreset::class);
        }

        if (! $options->interactive) {
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
    private function resolvePhpTools(InstallOptions $options, bool $installQualityGuidelines): array
    {
        $legacyBundle = $options->phpDeps;

        $resolved = [
            'legacy_bundle' => $legacyBundle,
            'phpstan' => $legacyBundle || $options->phpstan,
            'rector' => $legacyBundle || $options->rector,
            'pint' => $legacyBundle || $options->pint,
            'type_coverage' => $legacyBundle || $options->typeCoverage,
        ];

        if (! $installQualityGuidelines && ! $this->hasExplicitPhpToolSelection($options)) {
            return [
                'legacy_bundle' => false,
                'phpstan' => false,
                'rector' => false,
                'pint' => false,
                'type_coverage' => false,
            ];
        }

        if ($installQualityGuidelines && $options->interactive && ! $this->hasExplicitPhpToolSelection($options)) {
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
    private function resolveFrontendTools(InstallOptions $options, ProjectDetector $project): array
    {
        $legacyBundle = $options->frontendDeps;
        $enabled = $legacyBundle
            || $options->oxlint
            || $options->prettier
            || $options->concurrently
            || $options->ncu;

        $resolved = [
            'enabled' => $enabled,
            'legacy_bundle' => $legacyBundle,
            'oxlint' => $legacyBundle || $options->oxlint,
            'prettier' => $legacyBundle || $options->prettier,
            'concurrently' => $legacyBundle || $options->concurrently,
            'ncu' => $legacyBundle || $options->ncu,
        ];

        if (! $project->hasPackageJson()) {
            return $resolved;
        }

        if ($options->interactive && ! $this->hasExplicitFrontendToolSelection($options)) {
            $enabled = confirm(
                label: 'Install Bun frontend tooling?',
                default: true,
            );

            $resolved['enabled'] = $enabled;

            if (! $enabled) {
                return [
                    'enabled' => false,
                    'legacy_bundle' => false,
                    'oxlint' => false,
                    'prettier' => false,
                    'concurrently' => false,
                    'ncu' => false,
                ];
            }

            $resolved['legacy_bundle'] = true;
            $resolved['oxlint'] = true;
            $resolved['prettier'] = true;
            $resolved['concurrently'] = true;
            $resolved['ncu'] = true;
        }

        return $resolved;
    }

    private function hasExplicitPhpToolSelection(InstallOptions $options): bool
    {
        return $options->phpDeps
            || $options->phpstan
            || $options->rector
            || $options->pint
            || $options->typeCoverage;
    }

    private function hasExplicitFrontendToolSelection(InstallOptions $options): bool
    {
        return $options->frontendDeps
            || $options->oxlint
            || $options->prettier
            || $options->concurrently
            || $options->ncu;
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
            fn (string $value): AiGuidelinePreset => $this->parseEnumOption('ai', $value, AiGuidelinePreset::class),
            $values,
        );

        $filtered = array_values(
            array_filter(
                $presets,
                static fn (AiGuidelinePreset $preset): bool => $preset !== AiGuidelinePreset::None,
            ),
        );

        return $this->unique($filtered);
    }

    /**
     * @template T of BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return T
     */
    private function parseEnumOption(string $option, string $value, string $enum): BackedEnum
    {
        try {
            return $enum::from($value);
        } catch (ValueError) {
            $values = array_map(
                static fn (BackedEnum $case): string => (string) $case->value,
                $enum::cases(),
            );

            throw new InvalidArgumentException(sprintf(
                'Invalid --%s value [%s]. Supported values are: %s.',
                $option,
                $value,
                implode(', ', $values),
            ));
        }
    }

    /**
     * @template T
     *
     * @param  list<T>  $items
     * @return list<T>
     */
    private function unique(array $items): array
    {
        $unique = [];

        foreach ($items as $item) {
            if (! in_array($item, $unique, true)) {
                $unique[] = $item;
            }
        }

        return $unique;
    }
}
