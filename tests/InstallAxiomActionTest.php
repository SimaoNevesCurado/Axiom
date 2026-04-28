<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use SimaoCurado\Axiom\Actions\InstallAxiomAction;
use SimaoCurado\Axiom\Data\InstallSelections;
use SimaoCurado\Axiom\Enums\AiGuidelinePreset;
use SimaoCurado\Axiom\Enums\AuthRoutesPreset;
use SimaoCurado\Axiom\Enums\DebugToolPreset;

it('does not overwrite existing files without force', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/AGENTS.md', 'existing');
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'scripts' => [
            'test' => 'phpunit',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::Boost,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: false,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                installPhpQualityDependencies: false,
                installFrontendQualityDependencies: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        expect($result->written)->toBe([])
            ->and($result->skipped)->toBe(['AGENTS.md'])
            ->and(file_get_contents($basePath.'/AGENTS.md'))->toBe('existing');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('writes claude guidelines to a claude file', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::Claude,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: false,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                installPhpQualityDependencies: false,
                installFrontendQualityDependencies: false,
                overwriteFiles: true,
            ),
            $basePath,
        );

        expect($result->written)->toBe(['CLAUDE.md'])
            ->and($basePath.'/CLAUDE.md')->toBeFile()
            ->and(file_get_contents($basePath.'/CLAUDE.md'))->toContain('Axiom Claude Guidelines');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('writes multiple AI guideline files when multiple presets are selected', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                installPhpQualityDependencies: false,
                installFrontendQualityDependencies: false,
                overwriteFiles: true,
                aiGuidelinePresets: [AiGuidelinePreset::Codex, AiGuidelinePreset::Claude],
            ),
            $basePath,
        );

        expect($result->written)->toContain('AGENTS.md')
            ->toContain('CLAUDE.md')
            ->and($basePath.'/AGENTS.md')->toBeFile()
            ->and($basePath.'/CLAUDE.md')->toBeFile();
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('writes gemini and opencode guideline files when selected', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                installPhpQualityDependencies: false,
                installFrontendQualityDependencies: false,
                overwriteFiles: true,
                aiGuidelinePresets: [AiGuidelinePreset::Gemini, AiGuidelinePreset::Opencode],
            ),
            $basePath,
        );

        expect($result->written)->toContain('GEMINI.md')
            ->toContain('OPENCODE.md')
            ->and($basePath.'/GEMINI.md')->toBeFile()
            ->and($basePath.'/OPENCODE.md')->toBeFile();
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('writes vue-specific guideline stubs when the host project uses vue', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/package.json', json_encode([
        'name' => 'demo',
        'dependencies' => [
            'vue' => '^3.5.0',
            '@inertiajs/vue3' => '^2.0.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                installPhpQualityDependencies: false,
                installFrontendQualityDependencies: false,
                overwriteFiles: true,
                aiGuidelinePresets: [AiGuidelinePreset::Codex, AiGuidelinePreset::Claude, AiGuidelinePreset::Gemini],
            ),
            $basePath,
        );

        expect($result->written)->toContain('AGENTS.md')
            ->toContain('CLAUDE.md')
            ->toContain('GEMINI.md')
            ->and(file_get_contents($basePath.'/AGENTS.md'))->toContain('<laravel-boost-guidelines>')
            ->and(file_get_contents($basePath.'/CLAUDE.md'))->toContain('<laravel-boost-guidelines>')
            ->and(file_get_contents($basePath.'/GEMINI.md'))->toContain('<laravel-boost-guidelines>');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('writes react-specific guideline stubs when the host project uses react', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/package.json', json_encode([
        'name' => 'demo',
        'dependencies' => [
            'react' => '^19.0.0',
            '@inertiajs/react' => '^2.0.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                installPhpQualityDependencies: false,
                installFrontendQualityDependencies: false,
                overwriteFiles: true,
                aiGuidelinePresets: [AiGuidelinePreset::Codex, AiGuidelinePreset::Claude, AiGuidelinePreset::Gemini],
            ),
            $basePath,
        );

        expect($result->written)->toContain('AGENTS.md')
            ->toContain('CLAUDE.md')
            ->toContain('GEMINI.md')
            ->and(file_get_contents($basePath.'/AGENTS.md'))->toContain('<laravel-boost-guidelines>')
            ->and(file_get_contents($basePath.'/CLAUDE.md'))->toContain('<laravel-boost-guidelines>')
            ->and(file_get_contents($basePath.'/GEMINI.md'))->toContain('<laravel-boost-guidelines>');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('writes no-frontend guideline stubs when no frontend framework is detected', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/package.json', json_encode([
        'name' => 'demo',
        'dependencies' => [
            'lodash' => '^4.17.21',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                installPhpQualityDependencies: false,
                installFrontendQualityDependencies: false,
                overwriteFiles: true,
                aiGuidelinePresets: [AiGuidelinePreset::Codex, AiGuidelinePreset::Claude, AiGuidelinePreset::Gemini],
            ),
            $basePath,
        );

        expect($result->written)->toContain('AGENTS.md')
            ->toContain('CLAUDE.md')
            ->toContain('GEMINI.md')
            ->and(file_get_contents($basePath.'/AGENTS.md'))->toContain('# Axiom AGENTS')
            ->and(file_get_contents($basePath.'/CLAUDE.md'))->toContain('# Axiom Claude Guidelines')
            ->and(file_get_contents($basePath.'/GEMINI.md'))->toContain('# Axiom Gemini Guidelines')
            ->and(file_get_contents($basePath.'/AGENTS.md'))->not->toContain('<laravel-boost-guidelines>');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('creates actions, dto and enum folders when architecture is enabled', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: true,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                installPhpQualityDependencies: false,
                installFrontendQualityDependencies: false,
                overwriteFiles: true,
            ),
            $basePath,
        );

        expect($result->written)->toContain('app/Actions/.gitkeep')
            ->toContain('app/Dto/.gitkeep')
            ->toContain('app/Enums/.gitkeep')
            ->and($basePath.'/app/Actions/.gitkeep')->toBeFile()
            ->and($basePath.'/app/Dto/.gitkeep')->toBeFile()
            ->and($basePath.'/app/Enums/.gitkeep')->toBeFile();
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('adds recommended composer scripts to the host project', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'scripts' => [
            'post-autoload-dump' => '@php artisan package:discover --ansi',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/package.json', json_encode([
        'name' => 'demo',
        'private' => true,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: true,
                installPhpQualityDependencies: false,
                installFrontendQualityDependencies: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        /** @var array<string, mixed> $composer */
        $composer = json_decode((string) file_get_contents($basePath.'/composer.json'), true);

        expect($result->written)->toContain('composer.json')
            ->and(array_values(array_filter($result->written, static fn (string $path): bool => $path === 'composer.json')))->toHaveCount(1)
            ->and($composer['scripts'])->toHaveKey('setup')
            ->and($composer['scripts'])->toHaveKey('dev')
            ->and($composer['scripts'])->toHaveKey('fix:rector')
            ->and($composer['scripts'])->toHaveKey('lint')
            ->and($composer['scripts'])->toHaveKey('test')
            ->and($composer['scripts'])->toHaveKey('test:type-coverage')
            ->and($composer['scripts'])->toHaveKey('test:rector')
            ->and($composer['scripts'])->toHaveKey('test:unit')
            ->and($composer['scripts'])->toHaveKey('test:types')
            ->and($composer['scripts'])->toHaveKey('test:lint')
            ->and($composer['scripts'])->toHaveKey('update:requirements')
            ->and($composer['scripts']['fix:rector'])->toBe('rector')
            ->and($composer['scripts']['setup'])->toContain('bun install')
            ->and($composer['scripts']['test:rector'])->toBe('rector --dry-run')
            ->and($composer['scripts']['lint'])->toContain('bun run lint')
            ->and($composer['scripts']['test:types'])->toContain('bun run test:types')
            ->and($composer['scripts']['post-autoload-dump'])->toBe('@php artisan package:discover --ansi');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('adds backend-only composer scripts when the host project has no frontend package file', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: true,
                installPhpQualityDependencies: false,
                installFrontendQualityDependencies: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        /** @var array<string, mixed> $composer */
        $composer = json_decode((string) file_get_contents($basePath.'/composer.json'), true);

        expect($composer['scripts']['setup'])->not->toContain('bun install')
            ->and($composer['scripts']['fix:rector'])->toBe('rector')
            ->and($composer['scripts']['lint'])->not->toContain('bun run lint')
            ->and($composer['scripts']['test:rector'])->toBe('rector --dry-run')
            ->and($composer['scripts']['test:types'])->not->toContain('bun run test:types')
            ->and($composer['scripts']['update:requirements'])->toBe(['composer bump'])
            ->and($composer['scripts']['dev'])->toBe([
                'Composer\\Config::disableProcessTimeout',
                'php artisan serve',
            ]);
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('publishes ai skills when requested', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: true,
                authRoutes: AuthRoutesPreset::AppManaged,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                installPhpQualityDependencies: false,
                installFrontendQualityDependencies: false,
                overwriteFiles: true,
            ),
            $basePath,
        );

        expect($result->written)->toContain('.ai/skills/actions.md')
            ->toContain('.ai/skills/dto.md')
            ->toContain('.ai/skills/enum.md')
            ->toContain('.ai/skills/crud.md')
            ->toContain('.ai/skills/quality.md')
            ->and($basePath.'/.ai/skills/actions.md')->toBeFile()
            ->and($basePath.'/.ai/skills/dto.md')->toBeFile()
            ->and($basePath.'/.ai/skills/enum.md')->toBeFile()
            ->and($basePath.'/.ai/skills/crud.md')->toBeFile()
            ->and($basePath.'/.ai/skills/quality.md')->toBeFile();
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('publishes only selected ai skills when requested', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: true,
                authRoutes: AuthRoutesPreset::AppManaged,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                installPhpQualityDependencies: false,
                installFrontendQualityDependencies: false,
                overwriteFiles: true,
                aiSkills: ['actions', 'quality'],
            ),
            $basePath,
        );

        expect($result->written)->toContain('.ai/skills/actions.md')
            ->toContain('.ai/skills/quality.md')
            ->not->toContain('.ai/skills/dto.md')
            ->and($basePath.'/.ai/skills/actions.md')->toBeFile()
            ->and($basePath.'/.ai/skills/quality.md')->toBeFile()
            ->and($basePath.'/.ai/skills/dto.md')->not->toBeFile()
            ->and($basePath.'/.ai/skills/crud.md')->not->toBeFile();
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('publishes quality preset files and strict provider defaults', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: true,
                installStrictLaravelDefaults: true,
                installComposerScripts: false,
                installPhpQualityDependencies: false,
                installFrontendQualityDependencies: false,
                overwriteFiles: true,
            ),
            $basePath,
        );

        $providers = (string) file_get_contents($basePath.'/bootstrap/providers.php');
        $phpstan = (string) file_get_contents($basePath.'/phpstan.neon');

        expect($result->written)->toContain('phpstan.neon')
            ->toContain('rector.php')
            ->toContain('pint.json')
            ->toContain('tests/Unit/ArchTest.php')
            ->toContain('app/Providers/AxiomServiceProvider.php')
            ->toContain('bootstrap/providers.php')
            ->and($basePath.'/phpstan.neon')->toBeFile()
            ->and($basePath.'/rector.php')->toBeFile()
            ->and($basePath.'/pint.json')->toBeFile()
            ->and($basePath.'/tests/Unit/ArchTest.php')->toBeFile()
            ->and($basePath.'/app/Providers/AxiomServiceProvider.php')->toBeFile()
            ->and($phpstan)->toContain('vendor/nesbot/carbon/extension.neon')
            ->and($phpstan)->toContain('phar://phpstan.phar/conf/bleedingEdge.neon')
            ->and($phpstan)->toContain('- bootstrap/app.php')
            ->and($phpstan)->toContain('- public')
            ->and($phpstan)->toContain('tmpDir: /tmp/phpstan')
            ->and($providers)->toContain('App\\Providers\\AxiomServiceProvider::class');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('adds php quality dependencies to composer json when requested', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require-dev' => [
            'pestphp/pest' => '^4.4.3',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                installPhpQualityDependencies: true,
                installFrontendQualityDependencies: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        /** @var array<string, mixed> $composer */
        $composer = json_decode((string) file_get_contents($basePath.'/composer.json'), true);

        expect($composer['require-dev'])->toHaveKey('larastan/larastan')
            ->and($composer['require-dev'])->toHaveKey('rector/rector')
            ->and($composer['require-dev'])->toHaveKey('phpstan/phpstan')
            ->and($composer['require-dev'])->toHaveKey('pestphp/pest-plugin-type-coverage')
            ->and($composer['require-dev']['pestphp/pest'])->toBe('^4.4.3');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('does not add pest type coverage when laravel pao is installed', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/pao' => '^1.0.6',
        ],
        'require-dev' => [
            'pestphp/pest-plugin-laravel' => '^4.1',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                installPhpQualityDependencies: true,
                installFrontendQualityDependencies: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        /** @var array<string, mixed> $composer */
        $composer = json_decode((string) file_get_contents($basePath.'/composer.json'), true);

        expect($composer['require-dev'])->toHaveKey('larastan/larastan')
            ->and($composer['require-dev'])->toHaveKey('phpstan/phpstan')
            ->and($composer['require-dev'])->not->toHaveKey('pestphp/pest-plugin-type-coverage')
            ->and($composer['require']['laravel/pao'])->toBe('^1.0.6')
            ->and($composer['require-dev']['pestphp/pest-plugin-laravel'])->toBe('^4.1');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('adds only selected php tooling and debugbar when requested', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require-dev' => [],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                installPhpStan: true,
                installRector: true,
                debugTool: DebugToolPreset::Debugbar,
            ),
            $basePath,
        );

        /** @var array<string, mixed> $composer */
        $composer = json_decode((string) file_get_contents($basePath.'/composer.json'), true);

        expect($composer['require-dev'])->toHaveKey('larastan/larastan')
            ->and($composer['require-dev'])->toHaveKey('phpstan/phpstan')
            ->and($composer['require-dev'])->toHaveKey('driftingly/rector-laravel')
            ->and($composer['require-dev'])->toHaveKey('rector/rector')
            ->and($composer['require-dev'])->toHaveKey('barryvdh/laravel-debugbar')
            ->and($composer['require-dev']['barryvdh/laravel-debugbar'])->toBe('^4.2.6')
            ->and($composer['require-dev'])->not->toHaveKey('laravel/pint')
            ->and($composer['require-dev'])->not->toHaveKey('laravel/telescope');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('adds frontend quality dependencies to package json when requested', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/package.json', json_encode([
        'name' => 'demo',
        'devDependencies' => [
            'vite' => '^7.0.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                installPhpQualityDependencies: false,
                installFrontendQualityDependencies: true,
                overwriteFiles: false,
            ),
            $basePath,
        );

        /** @var array<string, mixed> $package */
        $package = json_decode((string) file_get_contents($basePath.'/package.json'), true);

        expect($package['devDependencies'])->toHaveKey('oxlint')
            ->and($package['devDependencies'])->toHaveKey('prettier')
            ->and($package['devDependencies'])->toHaveKey('concurrently')
            ->and($package['devDependencies']['vite'])->toBe('^7.0.0');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('adds only selected frontend tooling when requested', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/package.json', json_encode([
        'name' => 'demo',
        'devDependencies' => [],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                installOxlint: true,
                installPrettier: true,
            ),
            $basePath,
        );

        /** @var array<string, mixed> $package */
        $package = json_decode((string) file_get_contents($basePath.'/package.json'), true);

        expect($package['devDependencies'])->toHaveKey('oxlint')
            ->and($package['devDependencies'])->toHaveKey('prettier')
            ->and($package['devDependencies'])->toHaveKey('prettier-plugin-organize-imports')
            ->and($package['devDependencies'])->toHaveKey('prettier-plugin-tailwindcss')
            ->and($package['devDependencies'])->not->toHaveKey('concurrently')
            ->and($package['devDependencies'])->not->toHaveKey('npm-check-updates');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('adds the SSR process to the dev script when SSR is enabled', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/package.json', json_encode([
        'name' => 'demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installSsr: true,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: true,
                installPhpQualityDependencies: false,
                installFrontendQualityDependencies: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        /** @var array<string, mixed> $composer */
        $composer = json_decode((string) file_get_contents($basePath.'/composer.json'), true);

        expect($composer['scripts']['dev'][1])->toContain('php artisan inertia:start-ssr')
            ->and($composer['scripts']['dev'][1])->toContain('--names=server,queue,logs,vite,ssr');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('does not mutate composer require when fortify routes are enabled', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/package.json', json_encode([
        'name' => 'demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::Fortify,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        /** @var array<string, mixed> $composer */
        $composer = json_decode((string) file_get_contents($basePath.'/composer.json'), true);

        expect($composer['require'])->not->toHaveKey('laravel/fortify');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('does not touch existing Fortify files when Fortify routes are kept', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/app/Providers', 0777, true);
    mkdir($basePath.'/bootstrap', 0777, true);
    mkdir($basePath.'/config', 0777, true);
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
            'laravel/fortify' => '^1.36.1',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");
    file_put_contents($basePath.'/routes/web.php', "<?php\n\nRoute::get('/', fn (): string => 'ok');\n");
    file_put_contents($basePath.'/app/Providers/FortifyServiceProvider.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

final class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::twoFactorChallengeView(fn () => Inertia::render('user-two-factor-authentication-challenge/Show'));
        Fortify::confirmPasswordView(fn () => Inertia::render('user-password-confirmation/Create'));
    }
}
PHP);
    file_put_contents($basePath.'/config/fortify.php', <<<'PHP'
<?php

return [
    'views' => true,
];
PHP);

    $fortifyProviderBefore = (string) file_get_contents($basePath.'/app/Providers/FortifyServiceProvider.php');
    $fortifyConfigBefore = (string) file_get_contents($basePath.'/config/fortify.php');
    $webRoutesBefore = (string) file_get_contents($basePath.'/routes/web.php');

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::Fortify,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        expect($result->written)->not->toContain('app/Providers/FortifyServiceProvider.php')
            ->and($result->written)->not->toContain('config/fortify.php')
            ->and($result->written)->not->toContain('routes/web.php')
            ->and((string) file_get_contents($basePath.'/app/Providers/FortifyServiceProvider.php'))->toBe($fortifyProviderBefore)
            ->and((string) file_get_contents($basePath.'/config/fortify.php'))->toBe($fortifyConfigBefore)
            ->and((string) file_get_contents($basePath.'/routes/web.php'))->toBe($webRoutesBefore);
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('writes starter kit Fortify scaffolding in app managed mode when Fortify is installed', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
            'laravel/fortify' => '^1.36.1',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', <<<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', fn (): string => 'ok');
PHP);
    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        /** @var array<string, mixed> $composer */
        $composer = json_decode((string) file_get_contents($basePath.'/composer.json'), true);
        $providers = (string) file_get_contents($basePath.'/bootstrap/providers.php');
        $fortifyProvider = (string) file_get_contents($basePath.'/app/Providers/FortifyServiceProvider.php');
        $fortifyConfig = (string) file_get_contents($basePath.'/config/fortify.php');
        $webRoutes = (string) file_get_contents($basePath.'/routes/web.php');
        $settingsRoutePosition = strpos($webRoutes, "Route::delete('user', [UserController::class, 'destroy'])");
        $guestRoutePosition = strpos($webRoutes, "Route::get('register', [UserController::class, 'create'])");
        $verificationRoutePosition = strpos($webRoutes, "Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])");

        expect($result->written)->toContain('config/fortify.php')
            ->toContain('app/Providers/FortifyServiceProvider.php')
            ->toContain('bootstrap/providers.php')
            ->and($composer['require'])->toHaveKey('laravel/fortify')
            ->and($providers)->toContain('App\\Providers\\FortifyServiceProvider::class')
            ->and($fortifyProvider)->toContain('final class FortifyServiceProvider extends ServiceProvider')
            ->and($fortifyProvider)->toContain('use Laravel\\Fortify\\Fortify;')
            ->and($fortifyProvider)->toContain("Fortify::twoFactorChallengeView(fn () => Inertia::render('user-two-factor-authentication-challenge/Show'));")
            ->and($fortifyProvider)->toContain("Fortify::confirmPasswordView(fn () => Inertia::render('user-password-confirmation/Create'));")
            ->and($fortifyProvider)->not->toContain('Fortify::ignoreRoutes();')
            ->and($fortifyConfig)->toContain("'views' => true,")
            ->and($webRoutes)->toContain('// Axiom app-managed auth routes...')
            ->and($webRoutes)->toContain('    // User...')
            ->and($webRoutes)->toContain('    // User Profile...')
            ->and($webRoutes)->toContain('    // Session...')
            ->and($webRoutes)->toContain("Route::get('login', [SessionController::class, 'create'])")
            ->and($webRoutes)->toContain("Route::post('login', [SessionController::class, 'store'])")
            ->and($webRoutes)->toContain("Route::post('logout', [SessionController::class, 'destroy'])")
            ->and($webRoutes)->toContain("Route::get('register', [UserController::class, 'create'])")
            ->and($webRoutes)->toContain("Route::get('settings/profile', [UserProfileController::class, 'edit'])")
            ->and($webRoutes)->toContain("Route::get('settings/appearance', fn () => Inertia::render('appearance/Update'))")
            ->and($settingsRoutePosition)->toBeLessThan($guestRoutePosition)
            ->and($guestRoutePosition)->toBeLessThan($verificationRoutePosition)
            ->and($webRoutes)->not->toContain('// Axiom Fortify compatibility routes...');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('does not overwrite existing Fortify files in app managed mode without force', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/app/Providers', 0777, true);
    mkdir($basePath.'/bootstrap', 0777, true);
    mkdir($basePath.'/config', 0777, true);
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
            'laravel/fortify' => '^1.36.1',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n    App\\Providers\\FortifyServiceProvider::class,\n];\n");
    file_put_contents($basePath.'/routes/web.php', <<<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', fn (): string => 'ok');
PHP);
    file_put_contents($basePath.'/app/Providers/FortifyServiceProvider.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

final class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->bootFortifyDefaults();
        $this->bootRateLimitingDefaults();
    }

    private function bootFortifyDefaults(): void
    {
        Fortify::twoFactorChallengeView(fn () => Inertia::render('user-two-factor-authentication-challenge/Show'));
        Fortify::confirmPasswordView(fn () => Inertia::render('user-password-confirmation/Create'));
    }

    private function bootRateLimitingDefaults(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->string('email')->value().$request->ip()));
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));
    }
}
PHP);
    file_put_contents($basePath.'/config/fortify.php', <<<'PHP'
<?php

declare(strict_types=1);

use Laravel\Fortify\Features;

return [
    'views' => true,
    'features' => [
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]),
    ],
];
PHP);

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        $fortifyProvider = (string) file_get_contents($basePath.'/app/Providers/FortifyServiceProvider.php');
        $fortifyConfig = (string) file_get_contents($basePath.'/config/fortify.php');

        expect($result->written)->not->toContain('app/Providers/FortifyServiceProvider.php')
            ->and($result->written)->not->toContain('config/fortify.php')
            ->and($fortifyProvider)->toContain("Fortify::twoFactorChallengeView(fn () => Inertia::render('user-two-factor-authentication-challenge/Show'));")
            ->and($fortifyProvider)->toContain("Fortify::confirmPasswordView(fn () => Inertia::render('user-password-confirmation/Create'));")
            ->and($fortifyProvider)->not->toContain('Fortify::ignoreRoutes();')
            ->and($fortifyConfig)->toContain("'views' => true,");
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('overwrites existing Fortify provider with the starter kit provider when forced', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
            'laravel/fortify' => '^1.36.1',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n    App\\Providers\\FortifyServiceProvider::class,\n];\n");
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', "<?php\n\n");
    mkdir($basePath.'/app/Providers', 0777, true);
    file_put_contents($basePath.'/app/Providers/FortifyServiceProvider.php', <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::ignoreRoutes();
    }
}
PHP);

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: true,
            ),
            $basePath,
        );

        $fortifyProvider = (string) file_get_contents($basePath.'/app/Providers/FortifyServiceProvider.php');

        expect($fortifyProvider)->toContain('final class FortifyServiceProvider extends ServiceProvider')
            ->and($fortifyProvider)->toContain("Fortify::twoFactorChallengeView(fn () => Inertia::render('user-two-factor-authentication-challenge/Show'));")
            ->and($fortifyProvider)->toContain("Fortify::confirmPasswordView(fn () => Inertia::render('user-password-confirmation/Create'));")
            ->and($fortifyProvider)->not->toContain('Fortify::ignoreRoutes();');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('appends only missing app managed and compatibility routes when some auth routes already exist', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
            'laravel/fortify' => '^1.36.1',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', <<<'PHP'
<?php

declare(strict_types=1);

use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::post('login', [SessionController::class, 'store'])->name('login.store');
PHP);

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        $webRoutes = (string) file_get_contents($basePath.'/routes/web.php');

        expect($result->written)->toContain('routes/web.php')
            ->and($webRoutes)->toContain('// Axiom app-managed auth routes...')
            ->and(substr_count($webRoutes, "Route::post('login', [SessionController::class, 'store'])"))->toBe(1)
            ->and($webRoutes)->toContain("Route::post('logout', [SessionController::class, 'destroy'])")
            ->and($webRoutes)->toContain("Route::get('register', [UserController::class, 'create'])")
            ->and($webRoutes)->not->toContain('// Axiom Fortify compatibility routes...');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('does not append app managed or compatibility blocks when all auth routes already exist in routes/auth.php', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
            'laravel/fortify' => '^1.36.1',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', <<<'PHP'
<?php

declare(strict_types=1);

require __DIR__.'/auth.php';
PHP);
    file_put_contents($basePath.'/routes/auth.php', <<<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::delete('user', fn (): string => 'ok')->name('user.destroy');
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', fn (): string => 'ok')->name('user-profile.edit');
    Route::patch('settings/profile', fn (): string => 'ok')->name('user-profile.update');
    Route::get('settings/password', fn (): string => 'ok')->name('password.edit');
    Route::put('settings/password', fn (): string => 'ok')->name('password.update');
    Route::get('settings/appearance', fn (): string => 'ok')->name('appearance.edit');
    Route::get('settings/two-factor', fn (): string => 'ok')->name('two-factor.show');
    Route::get('verify-email', fn (): string => 'ok')->name('verification.notice');
    Route::post('email/verification-notification', fn (): string => 'ok')->name('verification.send');
    Route::get('verify-email/{id}/{hash}', fn (): string => 'ok')->name('verification.verify');
    Route::post('logout', fn (): string => 'ok')->name('logout');
});

Route::middleware('guest')->group(function (): void {
    Route::get('register', fn (): string => 'ok')->name('register');
    Route::post('register', fn (): string => 'ok')->name('register.store');
    Route::get('reset-password/{token}', fn (): string => 'ok')->name('password.reset');
    Route::post('reset-password', fn (): string => 'ok')->name('password.store');
    Route::get('forgot-password', fn (): string => 'ok')->name('password.request');
    Route::post('forgot-password', fn (): string => 'ok')->name('password.email');
    Route::get('login', fn (): string => 'ok')->name('login');
    Route::post('login', fn (): string => 'ok')->name('login.store');
});
PHP);

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        $webRoutes = (string) file_get_contents($basePath.'/routes/web.php');

        expect($result->written)->not->toContain('routes/web.php')
            ->and($result->skipped)->toContain('routes/web.php')
            ->and($webRoutes)->not->toContain('// Axiom app-managed auth routes...')
            ->and($webRoutes)->not->toContain('// Axiom Fortify compatibility routes...');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('cleans previously generated axiom route blocks when equivalent routes already exist outside web.php', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
            'laravel/fortify' => '^1.36.1',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', <<<'PHP'
<?php

declare(strict_types=1);

require __DIR__.'/auth.php';

// Axiom app-managed auth routes...
Route::middleware('auth')->group(function (): void {
    Route::delete('user', fn (): string => 'old')->name('user.destroy');
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', fn (): string => 'old')->name('user-profile.edit');
    Route::patch('settings/profile', fn (): string => 'old')->name('user-profile.update');
    Route::get('settings/password', fn (): string => 'old')->name('password.edit');
    Route::put('settings/password', fn (): string => 'old')->name('password.update');
    Route::get('settings/appearance', fn (): string => 'old')->name('appearance.edit');
    Route::get('settings/two-factor', fn (): string => 'old')->name('two-factor.show');
    Route::get('verify-email', fn (): string => 'old')->name('verification.notice');
    Route::post('email/verification-notification', fn (): string => 'old')->name('verification.send');
    Route::get('verify-email/{id}/{hash}', fn (): string => 'old')->name('verification.verify');
    Route::post('logout', fn (): string => 'old')->name('logout');
});

Route::middleware('guest')->group(function (): void {
    Route::get('register', fn (): string => 'old')->name('register');
    Route::post('register', fn (): string => 'old')->name('register.store');
    Route::get('reset-password/{token}', fn (): string => 'old')->name('password.reset');
    Route::post('reset-password', fn (): string => 'old')->name('password.store');
    Route::get('forgot-password', fn (): string => 'old')->name('password.request');
    Route::post('forgot-password', fn (): string => 'old')->name('password.email');
    Route::get('login', fn (): string => 'old')->name('login');
    Route::post('login', fn (): string => 'old')->name('login.store');
});
PHP);
    file_put_contents($basePath.'/routes/auth.php', <<<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::delete('user', fn (): string => 'ok')->name('user.destroy');
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', fn (): string => 'ok')->name('user-profile.edit');
    Route::patch('settings/profile', fn (): string => 'ok')->name('user-profile.update');
    Route::get('settings/password', fn (): string => 'ok')->name('password.edit');
    Route::put('settings/password', fn (): string => 'ok')->name('password.update');
    Route::get('settings/appearance', fn (): string => 'ok')->name('appearance.edit');
    Route::get('settings/two-factor', fn (): string => 'ok')->name('two-factor.show');
    Route::get('verify-email', fn (): string => 'ok')->name('verification.notice');
    Route::post('email/verification-notification', fn (): string => 'ok')->name('verification.send');
    Route::get('verify-email/{id}/{hash}', fn (): string => 'ok')->name('verification.verify');
    Route::post('logout', fn (): string => 'ok')->name('logout');
});

Route::middleware('guest')->group(function (): void {
    Route::get('register', fn (): string => 'ok')->name('register');
    Route::post('register', fn (): string => 'ok')->name('register.store');
    Route::get('reset-password/{token}', fn (): string => 'ok')->name('password.reset');
    Route::post('reset-password', fn (): string => 'ok')->name('password.store');
    Route::get('forgot-password', fn (): string => 'ok')->name('password.request');
    Route::post('forgot-password', fn (): string => 'ok')->name('password.email');
    Route::get('login', fn (): string => 'ok')->name('login');
    Route::post('login', fn (): string => 'ok')->name('login.store');
});
PHP);

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        $webRoutes = (string) file_get_contents($basePath.'/routes/web.php');

        expect($result->written)->toContain('routes/web.php')
            ->and($webRoutes)->toContain("require __DIR__.'/auth.php';")
            ->and($webRoutes)->not->toContain('// Axiom app-managed auth routes...')
            ->and($webRoutes)->not->toContain('// Axiom Fortify compatibility routes...');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('does not install app-managed auth scaffold when installAuthScaffold is disabled', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
            'laravel/fortify' => '^1.36.1',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n    App\\Providers\\FortifyServiceProvider::class,\n];\n");
    mkdir($basePath.'/resources/js/pages/auth', 0777, true);
    file_put_contents($basePath.'/resources/js/pages/auth/ConfirmPassword.vue', "<template />\n");
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', <<<'PHP'
<?php

declare(strict_types=1);

// Axiom app-managed auth routes...
Route::middleware('guest')->group(function (): void {
    Route::get('login', fn (): string => 'old')->name('login');
});
PHP);
    mkdir($basePath.'/app/Providers', 0777, true);
    file_put_contents($basePath.'/app/Providers/FortifyServiceProvider.php', <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Fortify::ignoreRoutes();
    }

    public function boot(): void
    {
        //
    }
}
PHP);

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: false,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        $webRoutes = (string) file_get_contents($basePath.'/routes/web.php');
        $fortifyProvider = (string) file_get_contents($basePath.'/app/Providers/FortifyServiceProvider.php');

        expect($result->written)->not->toContain('routes/web.php')
            ->and($result->written)->not->toContain('app/Providers/FortifyServiceProvider.php')
            ->and($webRoutes)->toContain('// Axiom app-managed auth routes...')
            ->and($fortifyProvider)->toContain('Fortify::ignoreRoutes();');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('installs app-managed auth scaffold when explicitly requested', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
            'laravel/fortify' => '^1.36.1',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n    App\\Providers\\FortifyServiceProvider::class,\n];\n");
    mkdir($basePath.'/resources/js/pages/auth', 0777, true);
    file_put_contents($basePath.'/resources/js/pages/auth/ConfirmPassword.vue', "<template />\n");
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', "<?php\n\n");
    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        $webRoutes = (string) file_get_contents($basePath.'/routes/web.php');
        $fortifyProvider = (string) file_get_contents($basePath.'/app/Providers/FortifyServiceProvider.php');

        expect($result->written)->toContain('routes/web.php')
            ->toContain('app/Providers/FortifyServiceProvider.php')
            ->toContain('config/fortify.php')
            ->and($webRoutes)->toContain('// Axiom app-managed auth routes...')
            ->and($webRoutes)->toContain("Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])")
            ->and($fortifyProvider)->toContain("Fortify::twoFactorChallengeView(fn () => Inertia::render('user-two-factor-authentication-challenge/Show'));")
            ->and($fortifyProvider)->not->toContain('Fortify::ignoreRoutes();');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('installs the starter kit session controller flow', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', "<?php\n\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        $sessionController = (string) file_get_contents($basePath.'/app/Http/Controllers/SessionController.php');

        expect($result->written)->toContain('app/Http/Controllers/SessionController.php')
            ->and($sessionController)->toContain("return Inertia::render('session/Create', [")
            ->and($sessionController)->toContain("'canResetPassword' => Route::has('password.request'),")
            ->and($sessionController)->toContain('if ($user->hasEnabledTwoFactorAuthentication())')
            ->and($sessionController)->not->toContain('Laravel\\Fortify\\Features');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('keeps auth scaffold backend-only when fortify is not installed', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', "<?php\n\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        /** @var array<string, mixed> $composer */
        $composer = json_decode((string) file_get_contents($basePath.'/composer.json'), true);
        $providers = (string) file_get_contents($basePath.'/bootstrap/providers.php');

        expect($result->written)->not->toContain('composer.json')
            ->and($result->written)->not->toContain('bootstrap/providers.php')
            ->and($result->written)->not->toContain('app/Providers/FortifyServiceProvider.php')
            ->and($composer['require'])->not->toHaveKey('laravel/fortify')
            ->and($providers)->not->toContain('App\\Providers\\FortifyServiceProvider::class')
            ->and(file_exists($basePath.'/app/Providers/FortifyServiceProvider.php'))->toBeFalse();
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('publishes the app managed auth backend scaffold when installing auth scaffold', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/package.json', json_encode([
        'name' => 'demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', "<?php\n\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        $sessionController = (string) file_get_contents($basePath.'/app/Http/Controllers/SessionController.php');
        $sessionRequest = (string) file_get_contents($basePath.'/app/Http/Requests/CreateSessionRequest.php');
        $userController = (string) file_get_contents($basePath.'/app/Http/Controllers/UserController.php');
        $profileController = (string) file_get_contents($basePath.'/app/Http/Controllers/UserProfileController.php');
        $profileRequest = (string) file_get_contents($basePath.'/app/Http/Requests/UpdateUserRequest.php');
        $createUserAction = (string) file_get_contents($basePath.'/app/Actions/CreateUser.php');
        $updateUserAction = (string) file_get_contents($basePath.'/app/Actions/UpdateUser.php');
        $webRoutes = (string) file_get_contents($basePath.'/routes/web.php');

        expect($result->written)->toContain('app/Http/Controllers/SessionController.php')
            ->toContain('app/Http/Controllers/UserController.php')
            ->toContain('app/Http/Controllers/UserProfileController.php')
            ->toContain('app/Http/Requests/CreateSessionRequest.php')
            ->toContain('app/Http/Requests/CreateUserRequest.php')
            ->toContain('app/Http/Requests/UpdateEmailVerificationRequest.php')
            ->toContain('app/Http/Requests/UpdateUserRequest.php')
            ->toContain('app/Actions/CreateUser.php')
            ->toContain('app/Actions/UpdateUser.php')
            ->toContain('app/Rules/ValidEmail.php')
            ->toContain('routes/web.php')
            ->and($sessionController)->toContain('public function create(Request $request): Response')
            ->and($sessionController)->toContain('public function store(CreateSessionRequest $request): RedirectResponse')
            ->and($sessionRequest)->toContain('public function validateCredentials(): User')
            ->and($userController)->toContain('public function create(): Response')
            ->and($profileController)->toContain('public function edit(Request $request): Response')
            ->and($profileController)->toContain('public function update(UpdateUserRequest $request, #[CurrentUser] User $user, UpdateUser $action): RedirectResponse')
            ->and($profileRequest)->toContain('Rule::unique(User::class)->ignore($user->id)')
            ->and($createUserAction)->toContain('event(new Registered($user));')
            ->and($updateUserAction)->toContain('$user->sendEmailVerificationNotification();')
            ->and($webRoutes)->toContain("Route::get('register', [UserController::class, 'create'])")
            ->and($webRoutes)->toContain("Route::post('register', [UserController::class, 'store'])")
            ->and($webRoutes)->toContain("Route::get('forgot-password', [UserEmailResetNotificationController::class, 'create'])")
            ->and($webRoutes)->toContain("Route::get('login', [SessionController::class, 'create'])")
            ->and($webRoutes)->toContain("Route::post('login', [SessionController::class, 'store'])")
            ->and($webRoutes)->toContain("Route::post('logout', [SessionController::class, 'destroy'])")
            ->and($webRoutes)->toContain("Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])")
            ->and($webRoutes)->toContain("Route::get('settings/profile', [UserProfileController::class, 'edit'])")
            ->and($webRoutes)->toContain("Route::get('settings/two-factor', [UserTwoFactorAuthenticationController::class, 'show'])")
            ->and(file_exists($basePath.'/resources/js/pages/session/Create.vue'))->toBeFalse()
            ->and(file_exists($basePath.'/resources/js/layouts/AuthLayout.vue'))->toBeFalse()
            ->and(file_exists($basePath.'/config/fortify.php'))->toBeFalse()
            ->and(file_exists($basePath.'/app/Providers/FortifyServiceProvider.php'))->toBeFalse();
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('uses react starter kit inertia page names for app managed auth stubs', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/package.json', json_encode([
        'name' => 'demo',
        'dependencies' => [
            '@inertiajs/react' => '^2.0',
            'react' => '^19.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
            'laravel/fortify' => '^1.36.1',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', "<?php\n\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        $sessionController = (string) file_get_contents($basePath.'/app/Http/Controllers/SessionController.php');
        $userController = (string) file_get_contents($basePath.'/app/Http/Controllers/UserController.php');
        $fortifyProvider = (string) file_get_contents($basePath.'/app/Providers/FortifyServiceProvider.php');
        $webRoutes = (string) file_get_contents($basePath.'/routes/web.php');

        expect($result->written)->toContain('app/Providers/FortifyServiceProvider.php')
            ->and($sessionController)->toContain("Inertia::render('session/create', [")
            ->and($userController)->toContain("Inertia::render('user/create')")
            ->and($fortifyProvider)->toContain("Inertia::render('user-two-factor-authentication-challenge/show')")
            ->and($webRoutes)->toContain("Route::get('settings/appearance', fn () => Inertia::render('appearance/update'))");
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('uses password store routes in vue reset password pages', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/package.json', json_encode([
        'name' => 'demo',
        'dependencies' => [
            '@inertiajs/vue3' => '^3.0',
            'vue' => '^3.5',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
            'laravel/fortify' => '^1.36.1',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', "<?php\n\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        $resetPasswordPage = (string) file_get_contents($basePath.'/resources/js/pages/user-password/Create.vue');

        expect($resetPasswordPage)->toContain("import { store } from '@/routes/password';")
            ->and($resetPasswordPage)->toContain('v-bind="store.form()"')
            ->and($resetPasswordPage)->not->toContain("import { update } from '@/routes/password';")
            ->and($resetPasswordPage)->not->toContain('v-bind="update.form()"');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('does not publish frontend auth assets even when frontend structure exists', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', "<?php\n\n");

    mkdir($basePath.'/resources/js/components/ui/button', 0777, true);
    mkdir($basePath.'/resources/js/components/ui/checkbox', 0777, true);
    mkdir($basePath.'/resources/js/components/ui/input', 0777, true);
    mkdir($basePath.'/resources/js/components/ui/label', 0777, true);
    mkdir($basePath.'/resources/js/components/ui/spinner', 0777, true);
    mkdir($basePath.'/resources/js/layouts', 0777, true);
    file_put_contents($basePath.'/resources/js/components/InputError.vue', "<template />\n");
    file_put_contents($basePath.'/resources/js/components/TextLink.vue', "<template />\n");
    file_put_contents($basePath.'/resources/js/layouts/AuthLayout.vue', "<template><slot /></template>\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        expect(file_exists($basePath.'/resources/js/pages/session/Create.vue'))->toBeFalse()
            ->and(file_exists($basePath.'/resources/js/pages/user/Create.vue'))->toBeFalse()
            ->and(file_exists($basePath.'/resources/css/axiom-auth.css'))->toBeFalse();
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('removes legacy Fortify action files and registration feature flags when installing app-managed auth', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/app/Actions/Fortify', 0777, true);
    file_put_contents($basePath.'/app/Actions/Fortify/CreateNewUser.php', "<?php\n\n");
    file_put_contents($basePath.'/app/Actions/Fortify/ResetUserPassword.php', "<?php\n\n");
    file_put_contents($basePath.'/app/Actions/Fortify/.gitkeep', '');
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', <<<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');
PHP);

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        $webRoutes = (string) file_get_contents($basePath.'/routes/web.php');

        expect($result->written)->toContain('app/Actions/Fortify/CreateNewUser.php')
            ->toContain('app/Actions/Fortify/ResetUserPassword.php')
            ->toContain('app/Actions/Fortify/.gitkeep')
            ->toContain('app/Actions/CreateUser.php')
            ->and(file_exists($basePath.'/app/Actions/Fortify'))->toBeFalse()
            ->and(file_exists($basePath.'/app/Actions/CreateUser.php'))->toBeTrue()
            ->and($webRoutes)->toContain("Route::inertia('/', 'Welcome')->name('home');")
            ->and($webRoutes)->not->toContain('canRegister')
            ->and($webRoutes)->not->toContain('Features::registration')
            ->and($webRoutes)->not->toContain('use Laravel\\Fortify\\Features;');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('migrates legacy Fortify starter auth files when app-managed auth is installed', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
            'laravel/fortify' => '^1.36.1',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/package.json', json_encode([
        'dependencies' => [
            '@inertiajs/vue3' => '^3.0',
            'vue' => '^3.5',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/app/Actions/Fortify', 0777, true);
    file_put_contents($basePath.'/app/Actions/Fortify/CreateNewUser.php', "<?php\n\n");
    file_put_contents($basePath.'/app/Actions/Fortify/ResetUserPassword.php', "<?php\n\n");
    mkdir($basePath.'/app/Providers', 0777, true);
    file_put_contents($basePath.'/app/Providers/FortifyServiceProvider.php', <<<'PHP'
<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::loginView(fn () => Inertia::render('auth/Login', [
            'canRegister' => Features::enabled(Features::registration()),
        ]));
        Fortify::registerView(fn () => Inertia::render('auth/Register'));
    }
}
PHP);
    mkdir($basePath.'/config', 0777, true);
    file_put_contents($basePath.'/config/fortify.php', <<<'PHP'
<?php

use Laravel\Fortify\Features;

return [
    'views' => true,
    'features' => [
        Features::registration(),
        Features::resetPasswords(),
        Features::emailVerification(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]),
    ],
];
PHP);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n    App\\Providers\\FortifyServiceProvider::class,\n];\n");
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', "<?php\n\n");
    file_put_contents($basePath.'/routes/settings.php', "<?php\n\n");
    mkdir($basePath.'/resources/js/pages/auth', 0777, true);
    file_put_contents($basePath.'/resources/js/pages/auth/Login.vue', "<template />\n");
    file_put_contents($basePath.'/resources/js/pages/auth/Register.vue', "<template />\n");
    mkdir($basePath.'/resources/js/pages/settings', 0777, true);

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authRoutes: AuthRoutesPreset::AppManaged,
                installAuthScaffold: true,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
            ),
            $basePath,
        );

        $fortifyProvider = (string) file_get_contents($basePath.'/app/Providers/FortifyServiceProvider.php');
        $fortifyConfig = (string) file_get_contents($basePath.'/config/fortify.php');

        expect($result->written)->toContain('app/Providers/FortifyServiceProvider.php')
            ->toContain('config/fortify.php')
            ->toContain('app/Actions/Fortify/CreateNewUser.php')
            ->toContain('resources/js/pages/auth/Login.vue')
            ->and($fortifyProvider)->toContain('final class FortifyServiceProvider extends ServiceProvider')
            ->and($fortifyProvider)->not->toContain('App\\Actions\\Fortify')
            ->and($fortifyProvider)->not->toContain('Fortify::loginView')
            ->and($fortifyProvider)->not->toContain('Features::enabled')
            ->and($fortifyConfig)->toContain('// Features::registration(),')
            ->and($fortifyConfig)->toContain('// Features::resetPasswords(),')
            ->and($fortifyConfig)->toContain('// Features::emailVerification(),')
            ->and(file_exists($basePath.'/app/Actions/Fortify'))->toBeFalse()
            ->and(file_exists($basePath.'/resources/js/pages/auth'))->toBeFalse()
            ->and(file_exists($basePath.'/resources/js/pages/user-profile/Edit.vue'))->toBeFalse()
            ->and(file_exists($basePath.'/resources/js/pages/user-password/Edit.vue'))->toBeFalse();
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

function deleteDirectoryForInstallActionTest(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    $items = scandir($path);

    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $itemPath = $path.'/'.$item;

        if (is_dir($itemPath)) {
            deleteDirectoryForInstallActionTest($itemPath);

            continue;
        }

        unlink($itemPath);
    }

    rmdir($path);
}
