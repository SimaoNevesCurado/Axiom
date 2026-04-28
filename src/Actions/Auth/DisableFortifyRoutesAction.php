<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Actions\EnsureUseImportsAction;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class DisableFortifyRoutesAction
{
    private EnsureUseImportsAction $ensureUseImports;

    public function __construct(private Filesystem $files)
    {
        $this->ensureUseImports = new EnsureUseImportsAction;
    }

    public function handle(InstallContext $context): void
    {
        $providerPath = $context->basePath.'/app/Providers/FortifyServiceProvider.php';

        if (! $this->files->exists($providerPath)) {
            $context->recordSkipped('app/Providers/FortifyServiceProvider.php');

            return;
        }

        $contents = (string) $this->files->get($providerPath);

        if (str_contains($contents, 'Fortify::ignoreRoutes();')) {
            $context->recordSkipped('app/Providers/FortifyServiceProvider.php');

            return;
        }

        $updated = $this->ensureUseImports->handle($contents, ['use Laravel\\Fortify\\Fortify;']);
        $updated = $this->ensureIgnoreRoutesInRegister($updated);

        if ($updated === $contents) {
            $context->recordSkipped('app/Providers/FortifyServiceProvider.php');

            return;
        }

        $this->files->put($providerPath, $updated);
        $context->recordWritten('app/Providers/FortifyServiceProvider.php');
    }

    private function ensureIgnoreRoutesInRegister(string $contents): string
    {
        if (preg_match('/public function register\(\): void\s*\{\n/', $contents, $match, PREG_OFFSET_CAPTURE) === 1) {
            $offset = $match[0][1] + strlen($match[0][0]);

            return substr($contents, 0, $offset)
                ."        Fortify::ignoreRoutes();\n"
                .substr($contents, $offset);
        }

        $method = "    public function register(): void\n    {\n        Fortify::ignoreRoutes();\n    }\n\n";

        if (preg_match('/^    public function boot\(\): void/m', $contents, $boot, PREG_OFFSET_CAPTURE) === 1) {
            return substr($contents, 0, $boot[0][1]).$method.substr($contents, $boot[0][1]);
        }

        if (preg_match('/^(final\s+)?class\s+FortifyServiceProvider[^{]*\{\s*\}/m', $contents) === 1) {
            return preg_replace(
                '/^(final\s+)?class\s+FortifyServiceProvider([^{]*)\{\s*\}/m',
                "$1class FortifyServiceProvider$2{\n".$method.'}',
                $contents,
                1,
            ) ?? $contents;
        }

        $position = strrpos($contents, "\n}");

        if ($position === false) {
            return $contents;
        }

        return substr($contents, 0, $position)."\n".$method.substr($contents, $position);
    }
}
