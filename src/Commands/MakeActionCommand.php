<?php

declare(strict_types=1);

namespace SimaoCurado\LaravelExtra\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use SimaoCurado\LaravelExtra\Support\GeneratesClasses;

final class MakeActionCommand extends Command
{
    use GeneratesClasses;

    protected $signature = 'make:action
        {name : The action class name}
        {--dto= : DTO class name to use in the handle signature}
        {--force : Overwrite the file if it already exists}';

    protected $description = 'Create a new action class in app/Actions';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $className = $this->resolveClassName(
            name: (string) $this->argument('name'),
            baseNamespace: 'App\\Actions',
            basePath: app_path('Actions'),
        );

        $dto = is_string($this->option('dto')) ? trim((string) $this->option('dto')) : '';

        if ($this->files->exists($className->path) && ! (bool) $this->option('force')) {
            $this->components->error('Action already exists.');

            return self::FAILURE;
        }

        $this->ensureDirectoryExists(dirname($className->path));

        $this->files->put(
            $className->path,
            $this->renderStub(
                stubPath: __DIR__.'/../../resources/stubs/generators/action.stub',
                replacements: [
                    '{{ namespace }}' => $className->namespace,
                    '{{ class }}' => $className->name,
                    '{{ imports }}' => $this->imports($dto),
                    '{{ handle_signature }}' => $this->handleSignature($dto),
                ],
            ),
        );

        $this->components->info("Action created: {$className->path}");

        return self::SUCCESS;
    }

    protected function laravelExtraFilesystem(): Filesystem
    {
        return $this->files;
    }

    private function imports(string $dto): string
    {
        if ($dto === '') {
            return '';
        }

        $normalizedDto = str_replace(['/', '\\'], '\\', $dto);
        $normalizedDto = trim($normalizedDto, '\\');
        $segments = array_values(array_filter(explode('\\', $normalizedDto)));
        $class = Str::studly((string) array_pop($segments));
        $namespace = 'App\\Dto'.($segments !== [] ? '\\'.implode('\\', array_map(static fn (string $segment): string => Str::studly($segment), $segments)) : '');

        return "use {$namespace}\\{$class};\n\n";
    }

    private function handleSignature(string $dto): string
    {
        if ($dto === '') {
            return 'handle(): void';
        }

        $normalizedDto = str_replace(['/', '\\'], '\\', $dto);
        $normalizedDto = trim($normalizedDto, '\\');
        $class = Str::studly((string) last(array_values(array_filter(explode('\\', $normalizedDto)))));
        $variable = Str::camel($class);

        return "handle({$class} \${$variable}): void";
    }
}
