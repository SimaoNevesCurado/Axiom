<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use SimaoCurado\Axiom\Actions\InstallAxiomAction;
use SimaoCurado\Axiom\Data\InstallSelections;
use SimaoCurado\Axiom\Enums\AiGuidelinePreset;
use SimaoCurado\Axiom\Enums\AuthScaffoldPreset;
use SimaoCurado\Axiom\Enums\DebugToolPreset;
use SimaoCurado\Axiom\Enums\FrontendStack;

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
                authScaffold: AuthScaffoldPreset::Fortify,
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
                authScaffold: AuthScaffoldPreset::Fortify,
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
                authScaffold: AuthScaffoldPreset::Fortify,
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
                authScaffold: AuthScaffoldPreset::Fortify,
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
                authScaffold: AuthScaffoldPreset::Fortify,
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
                authScaffold: AuthScaffoldPreset::Fortify,
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
                authScaffold: AuthScaffoldPreset::Fortify,
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
                authScaffold: AuthScaffoldPreset::Fortify,
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
                authScaffold: AuthScaffoldPreset::Fortify,
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
                authScaffold: AuthScaffoldPreset::Fortify,
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
                authScaffold: AuthScaffoldPreset::Fortify,
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
                authScaffold: AuthScaffoldPreset::AppManaged,
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

it('writes auth routes to a dedicated file and includes it from web routes', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::view('/', 'welcome');\n");
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authScaffold: AuthScaffoldPreset::AppManaged,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
                frontendStack: FrontendStack::InertiaVue,
            ),
            $basePath,
        );

        $webRoutes = (string) file_get_contents($basePath.'/routes/web.php');
        $authRoutes = (string) file_get_contents($basePath.'/routes/auth.php');
        $sessionController = (string) file_get_contents($basePath.'/app/Http/Controllers/SessionController.php');
        $validEmailRule = (string) file_get_contents($basePath.'/app/Rules/ValidEmail.php');

        expect($basePath.'/routes/auth.php')->toBeFile()
            ->and($basePath.'/app/Rules/ValidEmail.php')->toBeFile()
            ->and($webRoutes)->toContain("require __DIR__.'/auth.php';")
            ->and($authRoutes)->toContain("->name('login')")
            ->and($authRoutes)->toContain("->name('register')")
            ->and($authRoutes)->toContain("->name('password.request')")
            ->and($authRoutes)->toContain("->name('verification.notice')")
            ->and($authRoutes)->toContain("->middleware(['signed', 'throttle:6,1'])")
            ->and($sessionController)->toContain("Inertia::render('session/Create'")
            ->and($validEmailRule)->toContain('final readonly class ValidEmail');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('generates react auth scaffold files with react route entrypoints', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::view('/', 'welcome');\n");
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authScaffold: AuthScaffoldPreset::AppManaged,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
                frontendStack: FrontendStack::InertiaReact,
            ),
            $basePath,
        );

        expect($basePath.'/resources/js/pages/session/create.tsx')->toBeFile()
            ->and((string) file_get_contents($basePath.'/app/Http/Controllers/SessionController.php'))
            ->toContain("Inertia::render('session/create'");
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('generates blade auth scaffold files with blade views', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::view('/', 'welcome');\n");
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authScaffold: AuthScaffoldPreset::AppManaged,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
                frontendStack: FrontendStack::Blade,
            ),
            $basePath,
        );

        expect($basePath.'/resources/views/session/create.blade.php')->toBeFile()
            ->and((string) file_get_contents($basePath.'/app/Http/Controllers/SessionController.php'))
            ->toContain("return view('session.create'");
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('removes fortify leftover actions when app managed auth is installed', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::view('/', 'welcome');\n");
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");
    mkdir($basePath.'/app/Actions', 0777, true);
    file_put_contents($basePath.'/app/Actions/DeleteUser.php', '<?php');
    file_put_contents($basePath.'/app/Actions/UpdateUser.php', '<?php');
    file_put_contents($basePath.'/app/Actions/UpdateUserPassword.php', '<?php');
    mkdir($basePath.'/app/Actions/Fortify', 0777, true);
    file_put_contents($basePath.'/app/Actions/Fortify/CreateNewUser.php', '<?php');
    file_put_contents($basePath.'/app/Actions/Fortify/ResetUserPassword.php', '<?php');
    file_put_contents($basePath.'/app/Actions/Fortify/UpdateUserPassword.php', '<?php');
    file_put_contents($basePath.'/app/Actions/Fortify/UpdateUserProfileInformation.php', '<?php');
    mkdir($basePath.'/app/Http/Controllers', 0777, true);
    file_put_contents($basePath.'/app/Http/Controllers/UserProfileController.php', '<?php');
    file_put_contents($basePath.'/app/Http/Controllers/UserTwoFactorAuthenticationController.php', '<?php');

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authScaffold: AuthScaffoldPreset::AppManaged,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
                frontendStack: FrontendStack::InertiaVue,
            ),
            $basePath,
        );

        expect($basePath.'/app/Actions/DeleteUser.php')->not->toBeFile()
            ->and($basePath.'/app/Actions/UpdateUser.php')->not->toBeFile()
            ->and($basePath.'/app/Actions/UpdateUserPassword.php')->not->toBeFile()
            ->and($basePath.'/app/Actions/Fortify/CreateNewUser.php')->not->toBeFile()
            ->and($basePath.'/app/Actions/Fortify/ResetUserPassword.php')->not->toBeFile()
            ->and($basePath.'/app/Actions/Fortify/UpdateUserPassword.php')->not->toBeFile()
            ->and($basePath.'/app/Actions/Fortify/UpdateUserProfileInformation.php')->not->toBeFile()
            ->and($basePath.'/app/Http/Controllers/UserProfileController.php')->not->toBeFile()
            ->and($basePath.'/app/Http/Controllers/UserTwoFactorAuthenticationController.php')->not->toBeFile();
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('does not add routes auth file when auth routes already exist in web php', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents(
        $basePath.'/routes/web.php',
        "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::get('login', fn () => 'login')->name('login');\nRoute::post('logout', fn () => 'logout')->name('logout');\n",
    );
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallAxiomAction(new Filesystem);

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
                authScaffold: AuthScaffoldPreset::AppManaged,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
                frontendStack: FrontendStack::InertiaVue,
            ),
            $basePath,
        );

        $webRoutes = (string) file_get_contents($basePath.'/routes/web.php');

        expect($basePath.'/routes/auth.php')->not->toBeFile()
            ->and($webRoutes)->not->toContain("require __DIR__.'/auth.php';");
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('disables fortify route registration in fortify service provider', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::view('/', 'welcome');\n");
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");
    mkdir($basePath.'/app/Providers', 0777, true);
    file_put_contents($basePath.'/app/Providers/FortifyServiceProvider.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

final class FortifyServiceProvider extends ServiceProvider
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
                authScaffold: AuthScaffoldPreset::AppManaged,
                installSsr: false,
                installArchitectureGuidelines: false,
                installQualityGuidelines: false,
                installStrictLaravelDefaults: false,
                installComposerScripts: false,
                overwriteFiles: false,
                frontendStack: FrontendStack::InertiaVue,
            ),
            $basePath,
        );

        $provider = (string) file_get_contents($basePath.'/app/Providers/FortifyServiceProvider.php');

        expect($provider)->toContain('use Laravel\\Fortify\\Fortify;')
            ->and($provider)->toContain('Fortify::ignoreRoutes();');
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
