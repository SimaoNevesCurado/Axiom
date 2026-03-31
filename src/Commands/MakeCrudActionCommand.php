<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SimaoCurado\Axiom\Support\GeneratesClasses;

final class MakeCrudActionCommand extends Command
{
    use GeneratesClasses;

    protected $signature = 'make:crud-action
        {model : The model name, for example User}
        {--operation=create : CRUD operation: create, update, delete, show, list}
        {--dto= : DTO class to use for create or update operations}
        {--force : Overwrite the file if it already exists}';

    protected $description = 'Create a CRUD-oriented action class in app/Actions';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $operation = $this->operation();
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $model = Str::studly((string) $this->argument('model'));
        $className = $this->resolveClassName(
            name: $this->actionName($operation, $model),
            baseNamespace: 'App\\Actions',
            basePath: app_path('Actions'),
        );

        if ($this->files->exists($className->path) && ! (bool) $this->option('force')) {
            $this->components->error('CRUD action already exists.');

            return self::FAILURE;
        }

        $dto = is_string($this->option('dto')) ? trim((string) $this->option('dto')) : '';
        $imports = $this->imports($operation, $model, $dto);

        $this->ensureDirectoryExists(dirname($className->path));

        $this->files->put(
            $className->path,
            $this->renderStub(
                stubPath: __DIR__.'/../../resources/stubs/generators/crud-action.stub',
                replacements: [
                    '{{ namespace }}' => $className->namespace,
                    '{{ imports }}' => $imports === [] ? '' : implode("\n", $imports)."\n\n",
                    '{{ class }}' => $className->name,
                    '{{ handle_signature }}' => $this->handleSignature($operation, $model, $dto),
                    '{{ return_statement }}' => str_replace(
                        ['{{ model }}', '{{ modelVariable }}'],
                        [$model, $this->modelVariable($model)],
                        $this->returnStatement($operation),
                    ),
                ],
            ),
        );

        $this->components->info("CRUD action created: {$className->path}");

        return self::SUCCESS;
    }

    protected function laravelExtraFilesystem(): Filesystem
    {
        return $this->files;
    }

    private function operation(): string
    {
        $operation = strtolower((string) $this->option('operation'));
        $allowed = ['create', 'update', 'delete', 'show', 'list'];

        if (! in_array($operation, $allowed, true)) {
            throw new InvalidArgumentException('Invalid operation. Use create, update, delete, show, or list.');
        }

        return $operation;
    }

    private function actionName(string $operation, string $model): string
    {
        return match ($operation) {
            'create' => 'Create'.$model,
            'update' => 'Update'.$model,
            'delete' => 'Delete'.$model,
            'show' => 'Show'.$model,
            'list' => 'List'.$model.'s',
            default => 'Handle'.$model,
        };
    }

    /**
     * @return list<string>
     */
    private function imports(string $operation, string $model, string $dto): array
    {
        $imports = ["use App\\Models\\{$model};"];

        if ($dto !== '' && in_array($operation, ['create', 'update'], true)) {
            $imports[] = 'use '.$this->dtoFqcn($dto).';';
        }

        if ($operation === 'list') {
            $imports[] = 'use Illuminate\Database\Eloquent\Collection;';
        }

        return $imports;
    }

    private function handleSignature(string $operation, string $model, string $dto): string
    {
        return match ($operation) {
            'create' => $dto !== ''
                ? "handle({$this->dtoClass($dto)} \${$this->dtoVariable($dto)}): {$model}"
                : "handle(array \$attributes): {$model}",
            'update' => $dto !== ''
                ? "handle({$model} \${$this->modelVariable($model)}, {$this->dtoClass($dto)} \${$this->dtoVariable($dto)}): {$model}"
                : "handle({$model} \${$this->modelVariable($model)}, array \$attributes): {$model}",
            'delete' => "handle({$model} \${$this->modelVariable($model)}): void",
            'show' => "handle({$model} \${$this->modelVariable($model)}): {$model}",
            'list' => 'handle(): Collection',
            default => 'handle(): void',
        };
    }

    private function returnStatement(string $operation): string
    {
        return match ($operation) {
            'create' => '        return new {{ model }}();',
            'update' => '        return ${{ modelVariable }};',
            'delete' => '        //',
            'show' => '        return ${{ modelVariable }};',
            'list' => '        return {{ model }}::query()->get();',
            default => '        //',
        };
    }

    private function dtoFqcn(string $dto): string
    {
        $normalized = str_replace(['/', '\\'], '\\', $dto);
        $normalized = trim($normalized, '\\');
        $segments = array_values(array_filter(explode('\\', $normalized)));
        $class = Str::studly((string) array_pop($segments));

        return 'App\\Dto'.($segments !== [] ? '\\'.implode('\\', array_map(static fn (string $segment): string => Str::studly($segment), $segments)) : '').'\\'.$class;
    }

    private function dtoClass(string $dto): string
    {
        $normalized = str_replace(['/', '\\'], '\\', $dto);
        $normalized = trim($normalized, '\\');

        return Str::studly((string) last(array_values(array_filter(explode('\\', $normalized)))));
    }

    private function dtoVariable(string $dto): string
    {
        return Str::camel($this->dtoClass($dto));
    }

    private function modelVariable(string $model): string
    {
        return Str::camel($model);
    }
}
