<?php

use Illuminate\Support\Str;

it('installs the selected presets non-interactively', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/package.json', json_encode([
        'name' => 'demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    app()->setBasePath($basePath);

    try {
        $this->artisan('axiom:install', [
            '--ai' => 'boost',
            '--skills' => true,
            '--actions' => true,
            '--quality' => true,
            '--strict' => true,
            '--scripts' => true,
            '--phpstan' => true,
            '--rector' => true,
            '--pint' => true,
            '--type-coverage' => true,
            '--oxlint' => true,
            '--prettier' => true,
            '--concurrently' => true,
            '--ncu' => true,
            '--debug-tool' => 'debugbar',
            '--force' => true,
            '--no-interaction' => true,
        ])->assertExitCode(0);

        /** @var array<string, mixed> $composer */
        $composer = json_decode((string) file_get_contents($basePath.'/composer.json'), true);
        /** @var array<string, mixed> $package */
        $package = json_decode((string) file_get_contents($basePath.'/package.json'), true);

        expect($basePath.'/AGENTS.md')->toBeFile()
            ->and($basePath.'/.ai/skills/actions.md')->toBeFile()
            ->and($basePath.'/.ai/skills/dto.md')->toBeFile()
            ->and($basePath.'/.ai/architecture.md')->toBeFile()
            ->and($basePath.'/.ai/quality.md')->toBeFile()
            ->and($basePath.'/app/Actions/.gitkeep')->toBeFile()
            ->and($basePath.'/app/Dto/.gitkeep')->toBeFile()
            ->and($basePath.'/config/axiom.php')->toBeFile()
            ->and($basePath.'/app/Providers/AxiomServiceProvider.php')->toBeFile()
            ->and($composer['scripts'])->toHaveKey('setup')
            ->and($composer['scripts'])->toHaveKey('dev')
            ->and($composer['scripts'])->toHaveKey('fix:rector')
            ->and($composer['scripts'])->toHaveKey('lint')
            ->and($composer['scripts'])->toHaveKey('test')
            ->and($composer['scripts'])->toHaveKey('test:rector')
            ->and($composer['require-dev'])->toHaveKey('larastan/larastan')
            ->and($composer['require-dev'])->toHaveKey('rector/rector')
            ->and($composer['require-dev'])->toHaveKey('barryvdh/laravel-debugbar')
            ->and($package['devDependencies'])->toHaveKey('oxlint')
            ->and($package['devDependencies'])->toHaveKey('concurrently')
            ->and($package['devDependencies'])->toHaveKey('prettier');
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForInstallCommandTest($basePath);
    }
});

function deleteDirectoryForInstallCommandTest(string $path): void
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
            deleteDirectoryForInstallCommandTest($itemPath);

            continue;
        }

        unlink($itemPath);
    }

    rmdir($path);
}
