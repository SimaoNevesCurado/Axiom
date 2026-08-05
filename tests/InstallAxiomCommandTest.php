<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;
use SimaoCurado\Axiom\Support\ProjectDetector;
use Symfony\Component\Console\Output\BufferedOutput;

it('installs the selected presets non-interactively', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
            'laravel/fortify' => '^1.36.1',
        ],
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
            '--auth-routes' => 'fortify',
            '--ssr' => true,
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
            ->and($basePath.'/app/Enums/.gitkeep')->toBeFile()
            ->and($basePath.'/config/axiom.php')->toBeFile()
            ->and($basePath.'/app/Providers/AxiomServiceProvider.php')->toBeFile()
            ->and($composer['require']['laravel/fortify'])->toBe('^1.36.1')
            ->and($composer['scripts'])->toHaveKey('setup')
            ->and($composer['scripts'])->toHaveKey('dev')
            ->and($composer['scripts'])->toHaveKey('fix:rector')
            ->and($composer['scripts'])->toHaveKey('lint')
            ->and($composer['scripts'])->toHaveKey('test')
            ->and($composer['scripts']['dev'][1])->toContain('php artisan inertia:start-ssr')
            ->and($composer['scripts'])->toHaveKey('test:rector')
            ->and($composer['require-dev'])->toHaveKey('larastan/larastan')
            ->and($composer['require-dev'])->toHaveKey('rector/rector')
            ->and($composer['require-dev'])->toHaveKey('barryvdh/laravel-debugbar')
            ->and($composer['require-dev']['barryvdh/laravel-debugbar'])->toBe('^4.2.6')
            ->and($package['devDependencies'])->toHaveKey('oxlint')
            ->and($package['devDependencies'])->toHaveKey('concurrently')
            ->and($package['devDependencies'])->toHaveKey('prettier');
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForInstallCommandTest($basePath);
    }
});

it('keeps installed project files stable when run again without force', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/package.json', json_encode([
        'name' => 'demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    app()->setBasePath($basePath);

    $arguments = [
        '--ai' => 'boost,claude',
        '--skills' => true,
        '--actions' => true,
        '--quality' => true,
        '--strict' => true,
        '--scripts' => true,
        '--phpstan' => true,
        '--rector' => true,
        '--pint' => true,
        '--oxlint' => true,
        '--prettier' => true,
        '--no-interaction' => true,
    ];

    try {
        $this->artisan('axiom:install', $arguments)->assertExitCode(0);

        $files = [
            'AGENTS.md',
            'CLAUDE.md',
            '.ai/skills/actions.md',
            '.ai/architecture.md',
            '.ai/quality.md',
            'composer.json',
            'package.json',
            'bootstrap/providers.php',
            'app/Providers/AxiomServiceProvider.php',
        ];

        $before = snapshotInstallCommandFiles($basePath, $files);

        $this->artisan('axiom:install', $arguments)->assertExitCode(0);

        expect(snapshotInstallCommandFiles($basePath, $files))->toBe($before);
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForInstallCommandTest($basePath);
    }
});

it('reports dry run changes without touching the host project', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/package.json', json_encode([
        'name' => 'demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    $before = snapshotInstallCommandFiles($basePath, [
        'composer.json',
        'package.json',
        'bootstrap/providers.php',
    ]);

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
            '--oxlint' => true,
            '--dry-run' => true,
            '--no-interaction' => true,
        ])->assertExitCode(0);

        expect(snapshotInstallCommandFiles($basePath, [
            'composer.json',
            'package.json',
            'bootstrap/providers.php',
        ]))->toBe($before)
            ->and($basePath.'/AGENTS.md')->not->toBeFile()
            ->and($basePath.'/.ai/skills/actions.md')->not->toBeFile()
            ->and($basePath.'/app/Providers/AxiomServiceProvider.php')->not->toBeFile();
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForInstallCommandTest($basePath);
    }
});

it('outputs dry run results as json for automation', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/bootstrap', 0777, true);
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'acme/demo',
        'require' => [
            'laravel/framework' => '^12.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/package.json', json_encode([
        'name' => 'demo',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

    app()->setBasePath($basePath);

    try {
        [$exitCode, $output] = callAxiomInstallCommandWithBufferedOutput([
            '--ai' => 'boost',
            '--skills' => true,
            '--actions' => true,
            '--quality' => true,
            '--strict' => true,
            '--scripts' => true,
            '--phpstan' => true,
            '--oxlint' => true,
            '--dry-run' => true,
            '--json' => true,
            '--no-interaction' => true,
        ]);

        expect($exitCode)->toBe(0);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

        expect($payload['success'])->toBeTrue()
            ->and($payload['dryRun'])->toBeTrue()
            ->and($payload['planned'])->toContain('AGENTS.md')
            ->toContain('composer.json')
            ->and($payload['written'])->toBe([])
            ->and($payload['steps'])->toBeArray()
            ->and($payload['changes'])->toBeArray()
            ->and($output)->not->toContain('Axiom installer finished')
            ->and($basePath.'/AGENTS.md')->not->toBeFile();
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForInstallCommandTest($basePath);
    }
});

it('outputs invalid option failures as json', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    app()->setBasePath($basePath);

    try {
        [$exitCode, $output] = callAxiomInstallCommandWithBufferedOutput([
            '--ai' => 'invalid',
            '--json' => true,
            '--no-interaction' => true,
        ]);

        expect($exitCode)->toBe(1);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

        expect($payload['success'])->toBeFalse()
            ->and($payload['error'])->toContain('Invalid --ai value [invalid]')
            ->and($output)->not->toContain('ERROR');
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForInstallCommandTest($basePath);
    }
});

it('detects auth scaffold when login routes already exist in routes/web.php', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/routes', 0777, true);
    file_put_contents($basePath.'/routes/web.php', <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', fn (): string => 'ok')->name('login');
    Route::post('login', fn (): string => 'ok')->name('login.store');
});
PHP);

    app()->setBasePath($basePath);

    try {
        expect((new ProjectDetector)->hasAuthScaffold())->toBeTrue();
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForInstallCommandTest($basePath);
    }
});

it('installs app managed auth when explicitly selected in a Fortify project', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();
    $originalBasePath = base_path();

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
    file_put_contents($basePath.'/package.json', json_encode([
        'dependencies' => [
            '@inertiajs/vue3' => '^3.0',
            'vue' => '^3.5',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    file_put_contents($basePath.'/bootstrap/providers.php', "<?php\n\nreturn [\n    App\\Providers\\FortifyServiceProvider::class,\n];\n");
    file_put_contents($basePath.'/config/fortify.php', "<?php\n\nreturn ['views' => true];\n");
    file_put_contents($basePath.'/app/Providers/FortifyServiceProvider.php', "<?php\n\nnamespace App\\Providers;\n\nfinal class FortifyServiceProvider {}\n");
    file_put_contents($basePath.'/routes/web.php', <<<'PHP'
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');
PHP);

    app()->setBasePath($basePath);

    try {
        $this->artisan('axiom:install', [
            '--auth-routes' => 'app',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $webRoutes = (string) file_get_contents($basePath.'/routes/web.php');

        expect($basePath.'/app/Http/Controllers/SessionController.php')->toBeFile()
            ->and($basePath.'/app/Actions/CreateUser.php')->toBeFile()
            ->and($webRoutes)->toContain('// Axiom app-managed auth routes...')
            ->and($webRoutes)->toContain("Route::get('login', [SessionController::class, 'create'])")
            ->and($webRoutes)->toContain("Route::get('settings/appearance', fn () => Inertia::render('appearance/Update'))");
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForInstallCommandTest($basePath);
    }
});

/**
 * @param  list<string>  $files
 * @return array<string, string|null>
 */
function snapshotInstallCommandFiles(string $basePath, array $files): array
{
    $snapshot = [];

    foreach ($files as $file) {
        $path = $basePath.'/'.$file;
        $snapshot[$file] = file_exists($path) ? hash_file('sha256', $path) : null;
    }

    return $snapshot;
}

/**
 * @param  array<string, mixed>  $arguments
 * @return array{int, string}
 */
function callAxiomInstallCommandWithBufferedOutput(array $arguments): array
{
    $output = new BufferedOutput;
    $exitCode = app(Kernel::class)->call('axiom:install', $arguments, $output);

    return [$exitCode, $output->fetch()];
}

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
