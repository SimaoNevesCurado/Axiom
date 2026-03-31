<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Support\GeneratesClasses;

final class MakeRequestCommand extends Command
{
    use GeneratesClasses;

    protected $signature = 'make:request {name : The request class name} {--force : Overwrite the file if it already exists}';

    protected $description = 'Create a new form request in app/Http/Requests';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $className = $this->resolveClassName(
            name: (string) $this->argument('name'),
            baseNamespace: 'App\\Http\\Requests',
            basePath: app_path('Http/Requests'),
        );

        if ($this->files->exists($className->path) && ! (bool) $this->option('force')) {
            $this->components->error('Request already exists.');

            return self::FAILURE;
        }

        $this->ensureDirectoryExists(dirname($className->path));

        $this->files->put(
            $className->path,
            $this->renderStub(
                stubPath: __DIR__.'/../../resources/stubs/generators/request.stub',
                replacements: [
                    '{{ namespace }}' => $className->namespace,
                    '{{ class }}' => $className->name,
                ],
            ),
        );

        $this->components->info("Request created: {$className->path}");

        return self::SUCCESS;
    }

    protected function laravelExtraFilesystem(): Filesystem
    {
        return $this->files;
    }
}
