<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class RegisterBootstrapProviderAction
{
    public function __construct(private Filesystem $files) {}

    public function handle(InstallContext $context, string $provider): void
    {
        $providersPath = $context->basePath.'/bootstrap/providers.php';

        if (! $this->files->exists($providersPath)) {
            $context->recordSkipped('bootstrap/providers.php');

            return;
        }

        $contents = (string) $this->files->get($providersPath);

        if (str_contains($contents, $provider)) {
            $context->recordSkipped('bootstrap/providers.php');

            return;
        }

        $needle = '];';

        if (! str_contains($contents, $needle)) {
            $context->recordSkipped('bootstrap/providers.php');

            return;
        }

        $updated = str_replace($needle, "    {$provider},\n];", $contents);

        if ($updated === $contents && ! $context->selections->overwriteFiles) {
            $context->recordSkipped('bootstrap/providers.php');

            return;
        }

        $this->files->put($providersPath, $updated);

        $context->recordWritten('bootstrap/providers.php');
    }
}
