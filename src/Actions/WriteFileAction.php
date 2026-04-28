<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class WriteFileAction
{
    public function __construct(private Filesystem $files) {}

    public function handle(InstallContext $context, string $relativePath, string $content): void
    {
        $path = $context->basePath.'/'.$relativePath;

        if ($this->files->exists($path) && ! $context->selections->overwriteFiles) {
            $context->recordSkipped($relativePath);

            return;
        }

        $directory = dirname($path);

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($path, $content);

        $context->recordWritten($relativePath);
    }
}
