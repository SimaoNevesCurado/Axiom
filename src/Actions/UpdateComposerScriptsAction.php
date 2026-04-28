<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Data\InstallSelections;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class UpdateComposerScriptsAction
{
    public function __construct(private Filesystem $files) {}

    public function handle(InstallContext $context): void
    {
        if (! $context->selections->installComposerScripts) {
            return;
        }

        $composerPath = $context->basePath.'/composer.json';

        if (! $this->files->exists($composerPath)) {
            $context->recordSkipped('composer.json');

            return;
        }

        /** @var array<string, mixed>|null $composer */
        $composer = json_decode((string) $this->files->get($composerPath), true);

        if (! is_array($composer)) {
            $context->recordSkipped('composer.json');

            return;
        }

        $composer['scripts'] ??= [];

        if (! is_array($composer['scripts'])) {
            $context->recordSkipped('composer.json');

            return;
        }

        $scripts = $this->scripts($context->selections, $context->basePath);
        $hasChanges = false;

        foreach ($scripts as $name => $command) {
            if (array_key_exists($name, $composer['scripts']) && ! $context->selections->overwriteFiles) {
                continue;
            }

            if (! array_key_exists($name, $composer['scripts']) || $composer['scripts'][$name] !== $command) {
                $composer['scripts'][$name] = $command;
                $hasChanges = true;
            }
        }

        if (! $hasChanges) {
            $context->recordSkipped('composer.json');

            return;
        }

        ksort($composer['scripts']);

        $this->files->put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );

        $context->recordWritten('composer.json');
    }

    /**
     * @return array<string, string|list<string>>
     */
    private function scripts(InstallSelections $selections, string $basePath): array
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
