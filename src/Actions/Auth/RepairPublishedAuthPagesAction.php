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
        $this->repairReactHeadingImports($context);
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

    private function repairReactHeadingImports(InstallContext $context): void
    {
        foreach ([
            'resources/js/pages/appearance/update.tsx',
            'resources/js/pages/user-password/edit.tsx',
            'resources/js/pages/user-profile/edit.tsx',
            'resources/js/pages/user-two-factor-authentication/show.tsx',
        ] as $relativePath) {
            $path = $context->basePath.'/'.$relativePath;

            if (! $this->files->exists($path)) {
                continue;
            }

            $contents = (string) $this->files->get($path);
            $updated = str_replace(
                "import HeadingSmall from '@/components/heading-small';",
                "import Heading from '@/components/heading';",
                $contents,
            );
            $updated = str_replace('<HeadingSmall', '<Heading', $updated);

            foreach ([
                "Update your account's appearance settings",
                'Ensure your account is using a long, random password to stay secure',
                'Update your name and email address',
                'Manage your two-factor authentication settings',
            ] as $description) {
                $pattern = '/description="'.preg_quote($description, '/')."\"\n(\s*)\/>/";
                $replacement = "description=\"{$description}\"\n$1variant=\"small\"\n$1/>";
                $updated = preg_replace($pattern, $replacement, $updated) ?? $updated;
            }

            if ($updated === $contents) {
                continue;
            }

            $this->files->put($path, $updated);
            $context->recordWritten($relativePath);
        }
    }
}
