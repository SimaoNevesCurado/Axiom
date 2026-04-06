<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Data\InstallResult;
use SimaoCurado\Axiom\Data\InstallSelections;
use SimaoCurado\Axiom\Enums\AiGuidelinePreset;
use SimaoCurado\Axiom\Enums\DebugToolPreset;

final readonly class InstallAxiomAction
{
    public function __construct(private Filesystem $files) {}

    public function handle(InstallSelections $selections, string $basePath): InstallResult
    {
        $written = [];
        $skipped = [];

        if ($selections->installAiSkills) {
            $this->writeAiSkills(
                basePath: $basePath,
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
            );
        }

        if ($selections->installComposerScripts) {
            $this->writeComposerScripts(
                selections: $selections,
                basePath: $basePath,
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
            );
        }

        if ($this->composerDevDependencies($selections) !== []) {
            $this->writeComposerDevDependencies(
                selections: $selections,
                basePath: $basePath,
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
            );
        }

        if ($this->packageDevDependencies($selections) !== []) {
            $this->writePackageDevDependencies(
                selections: $selections,
                basePath: $basePath,
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
            );
        }

        if ($selections->aiGuidelines !== AiGuidelinePreset::None) {
            $this->writeFile(
                path: $this->aiGuidelinesPath($basePath, $selections->aiGuidelines),
                content: $this->stub($this->aiGuidelinesStub($selections->aiGuidelines)),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );
        }

        if ($selections->installArchitectureGuidelines) {
            $this->writeFile(
                path: $basePath.'/.ai/architecture.md',
                content: $this->stub('docs/architecture.stub'),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );

            $this->writeFile(
                path: $basePath.'/app/Actions/.gitkeep',
                content: '',
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );

            $this->writeFile(
                path: $basePath.'/app/Dto/.gitkeep',
                content: '',
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );
        }

        if ($selections->installQualityGuidelines) {
            $this->writeFile(
                path: $basePath.'/.ai/quality.md',
                content: $this->stub('docs/quality.stub'),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );

            $this->writeFile(
                path: $basePath.'/phpstan.neon',
                content: $this->stub('quality/phpstan.neon.stub'),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );

            $this->writeFile(
                path: $basePath.'/rector.php',
                content: $this->stub('quality/rector.php.stub'),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );

            $this->writeFile(
                path: $basePath.'/pint.json',
                content: $this->stub('quality/pint.json.stub'),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );

            $this->writeFile(
                path: $basePath.'/tests/Unit/ArchTest.php',
                content: $this->stub('quality/ArchTest.php.stub'),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );
        }

        if ($selections->installStrictLaravelDefaults) {
            $this->writeFile(
                path: $basePath.'/config/axiom.php',
                content: $this->stub('config/axiom.stub'),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );

            $this->writeFile(
                path: $basePath.'/app/Providers/AxiomServiceProvider.php',
                content: $this->stub('providers/AxiomServiceProvider.stub'),
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );

            $this->registerBootstrapProvider(
                basePath: $basePath,
                provider: 'App\\Providers\\AxiomServiceProvider::class',
                overwrite: $selections->overwriteFiles,
                written: $written,
                skipped: $skipped,
            );
        }

        return new InstallResult($written, $skipped);
    }

    private function aiGuidelinesPath(string $basePath, AiGuidelinePreset $preset): string
    {
        return match ($preset) {
            AiGuidelinePreset::Boost, AiGuidelinePreset::Codex => $basePath.'/AGENTS.md',
            AiGuidelinePreset::Claude => $basePath.'/CLAUDE.md',
            AiGuidelinePreset::None => $basePath.'/AGENTS.md',
        };
    }

    private function aiGuidelinesStub(AiGuidelinePreset $preset): string
    {
        return match ($preset) {
            AiGuidelinePreset::Boost => 'ai/AGENTS.boost.stub',
            AiGuidelinePreset::Codex => 'ai/AGENTS.codex.stub',
            AiGuidelinePreset::Claude => 'ai/CLAUDE.stub',
            AiGuidelinePreset::None => 'ai/AGENTS.codex.stub',
        };
    }

    /**
     * @param  list<string>  &$written
     * @param  list<string>  &$skipped
     */
    private function writeFile(
        string $path,
        string $content,
        bool $overwrite,
        array &$written,
        array &$skipped,
        string $basePath,
    ): void {
        if ($this->files->exists($path) && ! $overwrite) {
            $this->appendUnique($skipped, $this->relativePath($path, $basePath));

            return;
        }

        $directory = dirname($path);

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($path, $content);

        $this->appendUnique($written, $this->relativePath($path, $basePath));
    }

    private function stub(string $relativePath): string
    {
        return (string) $this->files->get(__DIR__.'/../../resources/stubs/'.$relativePath);
    }

    private function relativePath(string $path, string $basePath): string
    {
        return ltrim(str_replace($basePath, '', $path), '/');
    }

    /**
     * @param  list<string>  &$written
     * @param  list<string>  &$skipped
     */
    private function registerBootstrapProvider(
        string $basePath,
        string $provider,
        bool $overwrite,
        array &$written,
        array &$skipped,
    ): void {
        $providersPath = $basePath.'/bootstrap/providers.php';

        if (! $this->files->exists($providersPath)) {
            $this->appendUnique($skipped, 'bootstrap/providers.php');

            return;
        }

        $contents = (string) $this->files->get($providersPath);

        if (str_contains($contents, $provider)) {
            $this->appendUnique($skipped, 'bootstrap/providers.php');

            return;
        }

        $needle = '];';

        if (! str_contains($contents, $needle)) {
            $this->appendUnique($skipped, 'bootstrap/providers.php');

            return;
        }

        $updated = str_replace($needle, "    {$provider},\n];", $contents);

        if ($updated === $contents && ! $overwrite) {
            $this->appendUnique($skipped, 'bootstrap/providers.php');

            return;
        }

        $this->files->put($providersPath, $updated);

        $this->appendUnique($written, 'bootstrap/providers.php');
    }

    /**
     * @param  list<string>  &$written
     * @param  list<string>  &$skipped
     */
    private function writeAiSkills(
        string $basePath,
        bool $overwrite,
        array &$written,
        array &$skipped,
    ): void {
        $skills = [
            'actions' => 'skills/actions.stub',
            'dto' => 'skills/dto.stub',
            'enum' => 'skills/enum.stub',
            'crud' => 'skills/crud.stub',
            'quality' => 'skills/quality.stub',
        ];

        foreach ($skills as $name => $stub) {
            $this->writeFile(
                path: $basePath.'/.ai/skills/'.$name.'.md',
                content: $this->stub($stub),
                overwrite: $overwrite,
                written: $written,
                skipped: $skipped,
                basePath: $basePath,
            );
        }
    }

    /**
     * @param  list<string>  &$written
     * @param  list<string>  &$skipped
     */
    private function writeComposerScripts(
        InstallSelections $selections,
        string $basePath,
        bool $overwrite,
        array &$written,
        array &$skipped,
    ): void {
        $composerPath = $basePath.'/composer.json';

        if (! $this->files->exists($composerPath)) {
            $this->appendUnique($skipped, 'composer.json');

            return;
        }

        /** @var array<string, mixed>|null $composer */
        $composer = json_decode((string) $this->files->get($composerPath), true);

        if (! is_array($composer)) {
            $this->appendUnique($skipped, 'composer.json');

            return;
        }

        $composer['scripts'] ??= [];

        if (! is_array($composer['scripts'])) {
            $this->appendUnique($skipped, 'composer.json');

            return;
        }

        $scripts = $this->composerScripts($selections, $basePath);
        $hasChanges = false;

        foreach ($scripts as $name => $command) {
            if (array_key_exists($name, $composer['scripts']) && ! $overwrite) {
                continue;
            }

            if (! array_key_exists($name, $composer['scripts']) || $composer['scripts'][$name] !== $command) {
                $composer['scripts'][$name] = $command;
                $hasChanges = true;
            }
        }

        if (! $hasChanges) {
            $this->appendUnique($skipped, 'composer.json');

            return;
        }

        ksort($composer['scripts']);

        $this->files->put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );

        $this->appendUnique($written, 'composer.json');
    }

    /**
     * @param  list<string>  &$written
     * @param  list<string>  &$skipped
     */
    private function writeComposerDevDependencies(
        InstallSelections $selections,
        string $basePath,
        bool $overwrite,
        array &$written,
        array &$skipped,
    ): void {
        $composerPath = $basePath.'/composer.json';

        if (! $this->files->exists($composerPath)) {
            $this->appendUnique($skipped, 'composer.json');

            return;
        }

        /** @var array<string, mixed>|null $composer */
        $composer = json_decode((string) $this->files->get($composerPath), true);

        if (! is_array($composer)) {
            $this->appendUnique($skipped, 'composer.json');

            return;
        }

        $composer['require-dev'] ??= [];

        if (! is_array($composer['require-dev'])) {
            $this->appendUnique($skipped, 'composer.json');

            return;
        }

        $dependencies = $this->composerDevDependencies($selections);

        $hasChanges = false;

        foreach ($dependencies as $name => $version) {
            if (array_key_exists($name, $composer['require-dev']) && ! $overwrite) {
                continue;
            }

            if (! array_key_exists($name, $composer['require-dev']) || $composer['require-dev'][$name] !== $version) {
                $composer['require-dev'][$name] = $version;
                $hasChanges = true;
            }
        }

        if (! $hasChanges) {
            $this->appendUnique($skipped, 'composer.json');

            return;
        }

        ksort($composer['require-dev']);

        $this->files->put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );

        $this->appendUnique($written, 'composer.json');
    }

    /**
     * @param  list<string>  &$written
     * @param  list<string>  &$skipped
     */
    private function writePackageDevDependencies(
        InstallSelections $selections,
        string $basePath,
        bool $overwrite,
        array &$written,
        array &$skipped,
    ): void {
        $packagePath = $basePath.'/package.json';

        if (! $this->files->exists($packagePath)) {
            $this->appendUnique($skipped, 'package.json');

            return;
        }

        /** @var array<string, mixed>|null $package */
        $package = json_decode((string) $this->files->get($packagePath), true);

        if (! is_array($package)) {
            $this->appendUnique($skipped, 'package.json');

            return;
        }

        $package['devDependencies'] ??= [];

        if (! is_array($package['devDependencies'])) {
            $this->appendUnique($skipped, 'package.json');

            return;
        }

        $dependencies = $this->packageDevDependencies($selections);

        $hasChanges = false;

        foreach ($dependencies as $name => $version) {
            if (array_key_exists($name, $package['devDependencies']) && ! $overwrite) {
                continue;
            }

            if (! array_key_exists($name, $package['devDependencies']) || $package['devDependencies'][$name] !== $version) {
                $package['devDependencies'][$name] = $version;
                $hasChanges = true;
            }
        }

        if (! $hasChanges) {
            $this->appendUnique($skipped, 'package.json');

            return;
        }

        ksort($package['devDependencies']);

        $this->files->put(
            $packagePath,
            json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );

        $this->appendUnique($written, 'package.json');
    }

    /**
     * @param  list<string>  &$items
     */
    private function appendUnique(array &$items, string $value): void
    {
        if (! in_array($value, $items, true)) {
            $items[] = $value;
        }
    }

    /**
     * @return array<string, string>
     */
    private function composerDevDependencies(InstallSelections $selections): array
    {
        $dependencies = [];
        $installPhpStan = $selections->installPhpQualityDependencies || $selections->installPhpStan;
        $installRector = $selections->installPhpQualityDependencies || $selections->installRector;
        $installPint = $selections->installPhpQualityDependencies || $selections->installPint;
        $installTypeCoverage = $selections->installPhpQualityDependencies || $selections->installTypeCoverage;

        if ($installPhpStan) {
            $dependencies['larastan/larastan'] = '^3.9.3';
            $dependencies['phpstan/phpstan'] = '^2.1.45';
        }

        if ($installRector) {
            $dependencies['driftingly/rector-laravel'] = '^2.1.12';
            $dependencies['rector/rector'] = '^2.3.6';
        }

        if ($installPint) {
            $dependencies['laravel/pint'] = '^1.29.0';
        }

        if ($installTypeCoverage) {
            $dependencies['pestphp/pest-plugin-type-coverage'] = '^4.0.3';
        }

        if ($selections->debugTool === DebugToolPreset::Debugbar) {
            $dependencies['barryvdh/laravel-debugbar'] = '^3.0';
        }

        if ($selections->debugTool === DebugToolPreset::Telescope) {
            $dependencies['laravel/telescope'] = '^5.0';
        }

        ksort($dependencies);

        return $dependencies;
    }

    /**
     * @return array<string, string>
     */
    private function packageDevDependencies(InstallSelections $selections): array
    {
        $dependencies = [];
        $installFrontendBundle = $selections->installFrontendQualityDependencies;

        if ($installFrontendBundle || $selections->installConcurrently) {
            $dependencies['concurrently'] = '^9.2.1';
        }

        if ($installFrontendBundle || $selections->installNpmCheckUpdates) {
            $dependencies['npm-check-updates'] = '^19.3.2';
        }

        if ($installFrontendBundle || $selections->installOxlint) {
            $dependencies['oxlint'] = '^1.48.0';
        }

        if ($installFrontendBundle || $selections->installPrettier) {
            $dependencies['prettier'] = '^3.8.1';
            $dependencies['prettier-plugin-organize-imports'] = '^4.3.0';
            $dependencies['prettier-plugin-tailwindcss'] = '^0.7.2';
        }

        ksort($dependencies);

        return $dependencies;
    }

    /**
     * @return array<string, string|list<string>>
     */
    private function composerScripts(InstallSelections $selections, string $basePath): array
    {
        $hasFrontend = $this->files->exists($basePath.'/package.json');
        $useSsr = $hasFrontend && $selections->installSsr;

        $setup = [
            '@php -r "file_exists(\'.env\') || copy(\'.env.example\', \'.env\');"',
            '@configure:app-url',
            '@php artisan key:generate',
            '@php artisan migrate --force',
        ];

        if ($hasFrontend) {
            $setup[] = 'bun install';
            $setup[] = 'bun run build';
        }

        $dev = [
            'Composer\\Config::disableProcessTimeout',
        ];

        $dev[] = $hasFrontend
            ? ($useSsr
                ? 'bunx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74,#86efac" "php artisan serve" "php artisan queue:listen --tries=1" "php artisan pail --timeout=0" "bun run dev" "php artisan inertia:start-ssr" --names=server,queue,logs,vite,ssr --kill-others'
                : 'bunx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" "php artisan serve" "php artisan queue:listen --tries=1" "php artisan pail --timeout=0" "bun run dev" --names=server,queue,logs,vite --kill-others')
            : 'php artisan serve';

        $lint = [
            'rector',
            'pint --parallel',
        ];

        if ($hasFrontend) {
            $lint[] = 'bun run lint';
        }

        $testLint = [
            'pint --parallel --test',
            'rector --dry-run',
        ];

        if ($hasFrontend) {
            $testLint[] = 'bun run test:lint';
        }

        $testTypes = [
            'phpstan',
        ];

        if ($hasFrontend) {
            $testTypes[] = 'bun run test:types';
        }

        $updateRequirements = [
            'composer bump',
        ];

        if ($hasFrontend) {
            $updateRequirements[] = 'bunx npm-check-updates -u';
        }

        return [
            'configure:app-url' => [
                '@php -r "if (! file_exists(\'.env\')) { exit(0); } \$environment = file_get_contents(\'.env\'); \$directoryName = basename(getcwd()); \$slug = strtolower((string) preg_replace(\'/[^A-Za-z0-9]+/\', \'-\', \$directoryName)); \$slug = trim(\$slug, \'-\'); if (\$slug === \'\') { exit(0); } \$appUrl = \'http://\' . \$slug . \'.test\'; \$updatedEnvironment = preg_replace(\'/^APP_URL=.*/m\', \'APP_URL=\' . \$appUrl, \$environment, 1, \$replacements); if (\$replacements === 0) { \$updatedEnvironment .= PHP_EOL . \'APP_URL=\' . \$appUrl . PHP_EOL; } file_put_contents(\'.env\', \$updatedEnvironment);"',
            ],
            'dev' => $dev,
            'fix:rector' => 'rector',
            'lint' => $lint,
            'setup' => $setup,
            'test' => [
                '@test:type-coverage',
                '@test:unit',
                '@test:lint',
                '@test:rector',
                '@test:types',
            ],
            'test:lint' => $testLint,
            'test:rector' => 'rector --dry-run',
            'test:type-coverage' => 'pest --type-coverage --min=80',
            'test:types' => $testTypes,
            'test:unit' => 'pest --parallel',
            'update:requirements' => $updateRequirements,
        ];
    }
}
