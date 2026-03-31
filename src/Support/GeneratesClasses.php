<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

trait GeneratesClasses
{
    abstract protected function laravelExtraFilesystem(): Filesystem;

    protected function resolveClassName(string $name, string $baseNamespace, string $basePath): ClassName
    {
        $normalizedName = str_replace(['/', '\\'], '\\', trim($name));
        $normalizedName = trim($normalizedName, '\\');

        $segments = array_values(array_filter(explode('\\', $normalizedName)));
        $class = Str::studly((string) array_pop($segments));
        $subNamespace = implode('\\', array_map(static fn (string $segment): string => Str::studly($segment), $segments));

        $namespace = $baseNamespace.($subNamespace !== '' ? '\\'.$subNamespace : '');
        $directory = $basePath.($subNamespace !== '' ? '/'.str_replace('\\', '/', $subNamespace) : '');

        return new ClassName(
            name: $class,
            namespace: $namespace,
            path: $directory.'/'.$class.'.php',
        );
    }

    protected function ensureDirectoryExists(string $path): void
    {
        if (! $this->laravelExtraFilesystem()->isDirectory($path)) {
            $this->laravelExtraFilesystem()->makeDirectory($path, 0755, true);
        }
    }

    /**
     * @param  array<string, string>  $replacements
     */
    protected function renderStub(string $stubPath, array $replacements): string
    {
        $stub = (string) $this->laravelExtraFilesystem()->get($stubPath);

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }
}
