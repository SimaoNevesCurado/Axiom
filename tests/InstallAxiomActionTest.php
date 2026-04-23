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
            ->and($result->skipped)->toBe(['AGENTS.md', 'app/Providers/FortifyServiceProvider.php'])
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

it('creates actions and dto folders when architecture is enabled', function () {
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
            ->and($basePath.'/app/Actions/.gitkeep')->toBeFile()
            ->and($basePath.'/app/Dto/.gitkeep')->toBeFile();
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
            ->and($composer['require-dev']['pestphp/pest'])->toBe('^4.4.3');
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

it('adds Fortify ignoreRoutes in app managed mode', function () {
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
    file_put_contents($basePath.'/routes/web.php', <<<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', fn (): string => 'ok');
PHP);
    mkdir($basePath.'/app/Providers', 0777, true);
    file_put_contents($basePath.'/app/Providers/FortifyServiceProvider.php', <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
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
                overwriteFiles: false,
            ),
            $basePath,
        );

        /** @var array<string, mixed> $composer */
        $composer = json_decode((string) file_get_contents($basePath.'/composer.json'), true);
        $providers = (string) file_get_contents($basePath.'/bootstrap/providers.php');
        $fortifyProvider = (string) file_get_contents($basePath.'/app/Providers/FortifyServiceProvider.php');
        $webRoutes = (string) file_get_contents($basePath.'/routes/web.php');

        expect($composer['require'])->toHaveKey('laravel/fortify')
            ->and($providers)->toContain('App\\Providers\\FortifyServiceProvider::class')
            ->and($fortifyProvider)->toContain('use Laravel\\Fortify\\Fortify;')
            ->and($fortifyProvider)->toContain('Fortify::ignoreRoutes();')
            ->and($webRoutes)->toContain('// Axiom app-managed auth routes...')
            ->and($webRoutes)->toContain("Route::get('login', [SessionController::class, 'create'])")
            ->and($webRoutes)->toContain("Route::post('logout', [SessionController::class, 'destroy'])")
            ->and($webRoutes)->toContain('// Axiom Fortify compatibility routes...')
            ->and($webRoutes)->toContain("->name('two-factor.login');")
            ->and($webRoutes)->toContain("->name('password.confirm');");
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('does not duplicate app managed auth routes when login route already exists, while adding fortify compatibility routes', function () {
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

Route::get('login', [SessionController::class, 'create'])->name('login');
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
            ->and($webRoutes)->not->toContain('// Axiom app-managed auth routes...')
            ->and($webRoutes)->toContain('// Axiom Fortify compatibility routes...')
            ->and($webRoutes)->toContain("->name('two-factor.login');")
            ->and($webRoutes)->toContain("->name('password.confirm');");
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
