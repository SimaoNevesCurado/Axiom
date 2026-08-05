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
            $context->recordSkipped($relativePath, 'File already exists. Use --force to overwrite it.');

            return;
        }

        $context->putFile($this->files, $relativePath, $content);
    }
}
