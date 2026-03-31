<?php

declare(strict_types=1);

namespace SimaoCurado\LaravelExtra\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use SimaoCurado\LaravelExtra\Support\GeneratesClasses;

final class MakeEnumCommand extends Command
{
    use GeneratesClasses;

    protected $signature = 'make:enum
        {name : The enum class name}
        {--int : Generate an int-backed enum instead of string-backed}
        {--force : Overwrite the file if it already exists}';

    protected $description = 'Create a new string-backed enum in app/Enums';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $className = $this->resolveClassName(
            name: (string) $this->argument('name'),
            baseNamespace: 'App\\Enums',
            basePath: app_path('Enums'),
        );

        if ($this->files->exists($className->path) && ! (bool) $this->option('force')) {
            $this->components->error('Enum already exists.');

            return self::FAILURE;
        }

        $this->ensureDirectoryExists(dirname($className->path));

        $this->files->put(
            $className->path,
            $this->renderStub(
                stubPath: __DIR__.'/../../resources/stubs/generators/enum.stub',
                replacements: [
                    '{{ namespace }}' => $className->namespace,
                    '{{ class }}' => $className->name,
                    '{{ type }}' => (bool) $this->option('int') ? 'int' : 'string',
                    '{{ example_value }}' => (bool) $this->option('int') ? '1' : "'example'",
                ],
            ),
        );

        $this->components->info("Enum created: {$className->path}");

        return self::SUCCESS;
    }

    protected function laravelExtraFilesystem(): Filesystem
    {
        return $this->files;
    }
}
