<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use SimaoCurado\LaravelExtra\Actions\InstallLaravelExtraAction;
use SimaoCurado\LaravelExtra\Data\InstallSelections;
use SimaoCurado\LaravelExtra\Enums\AiGuidelinePreset;

it('does not overwrite existing files without force', function () {
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();

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

    $action = new InstallLaravelExtraAction(new Filesystem());

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::Boost,
                installAiSkills: false,
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
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallLaravelExtraAction(new Filesystem());

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::Claude,
                installAiSkills: false,
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
            ->and(file_get_contents($basePath.'/CLAUDE.md'))->toContain('Laravel Extra Claude Guidelines');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('creates actions and data folders when architecture is enabled', function () {
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallLaravelExtraAction(new Filesystem());

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
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
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();

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

    $action = new InstallLaravelExtraAction(new Filesystem());

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
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
            ->and($composer['scripts'])->toHaveKey('setup')
            ->and($composer['scripts'])->toHaveKey('dev')
            ->and($composer['scripts'])->toHaveKey('lint')
            ->and($composer['scripts'])->toHaveKey('test')
            ->and($composer['scripts'])->toHaveKey('test:type-coverage')
            ->and($composer['scripts'])->toHaveKey('test:unit')
            ->and($composer['scripts'])->toHaveKey('test:types')
            ->and($composer['scripts'])->toHaveKey('test:lint')
            ->and($composer['scripts'])->toHaveKey('update:requirements')
            ->and($composer['scripts']['setup'])->toContain('bun install')
            ->and($composer['scripts']['lint'])->toContain('bun run lint')
            ->and($composer['scripts']['test:types'])->toContain('bun run test:types')
            ->and($composer['scripts']['post-autoload-dump'])->toBe('@php artisan package:discover --ansi');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('adds backend-only composer scripts when the host project has no frontend package file', function () {
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallLaravelExtraAction(new Filesystem());

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
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
            ->and($composer['scripts']['lint'])->not->toContain('bun run lint')
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
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallLaravelExtraAction(new Filesystem());

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: true,
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
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallLaravelExtraAction(new Filesystem());

    try {
        $result = $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
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

        expect($result->written)->toContain('phpstan.neon')
            ->toContain('rector.php')
            ->toContain('pint.json')
            ->toContain('tests/Unit/ArchTest.php')
            ->toContain('app/Providers/LaravelExtraServiceProvider.php')
            ->toContain('bootstrap/providers.php')
            ->and($basePath.'/phpstan.neon')->toBeFile()
            ->and($basePath.'/rector.php')->toBeFile()
            ->and($basePath.'/pint.json')->toBeFile()
            ->and($basePath.'/tests/Unit/ArchTest.php')->toBeFile()
            ->and($basePath.'/app/Providers/LaravelExtraServiceProvider.php')->toBeFile()
            ->and($providers)->toContain('App\\Providers\\LaravelExtraServiceProvider::class');
    } finally {
        deleteDirectoryForInstallActionTest($basePath);
    }
});

it('adds php quality dependencies to composer json when requested', function () {
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require-dev' => [
            'pestphp/pest' => '^4.4.3',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $action = new InstallLaravelExtraAction(new Filesystem());

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
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

it('adds frontend quality dependencies to package json when requested', function () {
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();

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

    $action = new InstallLaravelExtraAction(new Filesystem());

    try {
        $action->handle(
            new InstallSelections(
                aiGuidelines: AiGuidelinePreset::None,
                installAiSkills: false,
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
