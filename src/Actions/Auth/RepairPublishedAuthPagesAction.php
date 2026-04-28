<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class RepairPublishedAuthPagesAction
{
    public function __construct(private Filesystem $files) {}

    public function handle(InstallContext $context): void
    {
        $this->repairVueResetPasswordPage($context);
    }

    private function repairVueResetPasswordPage(InstallContext $context): void
    {
        $relativePath = 'resources/js/pages/user-password/Create.vue';
        $path = $context->basePath.'/'.$relativePath;

        if (! $this->files->exists($path)) {
            return;
        }

        $contents = (string) $this->files->get($path);
        $updated = str_replace(
            [
                "import { update } from '@/routes/password';",
                'v-bind="update.form()"',
            ],
            [
                "import { store } from '@/routes/password';",
                'v-bind="store.form()"',
            ],
            $contents,
        );

        if ($updated === $contents) {
            return;
        }

        $this->files->put($path, $updated);
        $context->recordWritten($relativePath);
    }
}
