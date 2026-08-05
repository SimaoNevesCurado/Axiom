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
            $context->recordSkipped('bootstrap/providers.php', 'bootstrap/providers.php does not exist.');

            return;
        }

        $contents = (string) $this->files->get($providersPath);

        if (str_contains($contents, $provider)) {
            $context->recordSkipped('bootstrap/providers.php', 'Provider is already registered.');

            return;
        }

        $needle = '];';

        if (! str_contains($contents, $needle)) {
            $context->recordSkipped('bootstrap/providers.php', 'Could not find providers array terminator.');

            return;
        }

        $updated = str_replace($needle, "    {$provider},\n];", $contents);

        if ($updated === $contents && ! $context->selections->overwriteFiles) {
            $context->recordSkipped('bootstrap/providers.php', 'No provider registration changes were needed.');

            return;
        }

        $context->putFile($this->files, 'bootstrap/providers.php', $updated);
    }
}
