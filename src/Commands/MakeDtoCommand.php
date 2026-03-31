<?php

declare(strict_types=1);

namespace SimaoCurado\LaravelExtra\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use SimaoCurado\LaravelExtra\Support\GeneratesClasses;
use SimaoCurado\LaravelExtra\Support\GeneratorInput;

use InvalidArgumentException;

final class MakeDtoCommand extends Command
{
    use GeneratesClasses;

    protected $signature = 'make:dto
        {name : The DTO class name}
        {--property=* : Constructor property definition in the form name:type}
        {--force : Overwrite the file if it already exists}';

    protected $description = 'Create a new readonly DTO class in app/Dto';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $properties = GeneratorInput::properties((array) $this->option('property'));
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $className = $this->resolveClassName(
            name: (string) $this->argument('name'),
            baseNamespace: 'App\\Dto',
            basePath: app_path('Dto'),
        );

        if ($this->files->exists($className->path) && ! (bool) $this->option('force')) {
            $this->components->error('DTO class already exists.');

            return self::FAILURE;
        }

        $this->ensureDirectoryExists(dirname($className->path));

        $this->files->put(
            $className->path,
            $this->renderStub(
                stubPath: __DIR__.'/../../resources/stubs/generators/dto.stub',
                replacements: [
                    '{{ namespace }}' => $className->namespace,
                    '{{ class }}' => $className->name,
                    '{{ constructor }}' => $this->constructor($properties),
                ],
            ),
        );

        $this->components->info("DTO class created: {$className->path}");

        return self::SUCCESS;
    }

    protected function laravelExtraFilesystem(): Filesystem
    {
        return $this->files;
    }

    /**
     * @param  list<array{name: string, type: string}>  $properties
     */
    private function constructor(array $properties): string
    {
        if ($properties === []) {
            return "    public function __construct()\n    {\n    }";
        }

        $arguments = array_map(
            static fn (array $property): string => "        public {$property['type']} \${$property['name']}",
            $properties,
        );

        return "    public function __construct(\n".implode(",\n", $arguments)."\n    ) {\n    }";
    }
}
