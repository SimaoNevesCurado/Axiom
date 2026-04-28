<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Actions\DetectFrontendStackAction;
use SimaoCurado\Axiom\Actions\ResolveStubPathAction;
use SimaoCurado\Axiom\Actions\WriteFileAction;
use SimaoCurado\Axiom\Enums\FrontendStack;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class PublishAuthPagesAction
{
    private Filesystem $files;

    private DetectFrontendStackAction $detectFrontendStack;

    private ResolveStubPathAction $stubs;

    private WriteFileAction $writeFile;

    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        $this->detectFrontendStack = new DetectFrontendStackAction($files);
        $this->stubs = new ResolveStubPathAction($files);
        $this->writeFile = new WriteFileAction($files);
    }

    public function handle(InstallContext $context): void
    {
        $frontendStack = $this->detectFrontendStack->handle($context->basePath);

        foreach ($this->pageStubs($context, $frontendStack) as $target => $stub) {
            $this->writeFile->handle($context, $target, $this->stubs->contents($stub));
        }
    }

    /**
     * @return array<string, string>
     */
    private function pageStubs(InstallContext $context, FrontendStack $frontendStack): array
    {
        $pages = match ($frontendStack) {
            FrontendStack::React => [
                'resources/js/pages/appearance/update.tsx' => 'auth/pages/react/appearance/update.tsx.stub',
                'resources/js/pages/session/create.tsx' => 'auth/pages/react/session/create.tsx.stub',
                'resources/js/pages/user/create.tsx' => 'auth/pages/react/user/create.tsx.stub',
                'resources/js/pages/user-email-reset-notification/create.tsx' => 'auth/pages/react/user-email-reset-notification/create.tsx.stub',
                'resources/js/pages/user-email-verification-notification/create.tsx' => 'auth/pages/react/user-email-verification-notification/create.tsx.stub',
                'resources/js/pages/user-password/create.tsx' => 'auth/pages/react/user-password/create.tsx.stub',
                'resources/js/pages/user-password/edit.tsx' => 'auth/pages/react/user-password/edit.tsx.stub',
                'resources/js/pages/user-password-confirmation/create.tsx' => 'auth/pages/react/user-password-confirmation/create.tsx.stub',
                'resources/js/pages/user-profile/edit.tsx' => 'auth/pages/react/user-profile/edit.tsx.stub',
                'resources/js/pages/user-two-factor-authentication/show.tsx' => 'auth/pages/react/user-two-factor-authentication/show.tsx.stub',
                'resources/js/pages/user-two-factor-authentication-challenge/show.tsx' => 'auth/pages/react/user-two-factor-authentication-challenge/show.tsx.stub',
            ],
            FrontendStack::Vue => [
                'resources/js/pages/appearance/Update.vue' => 'auth/pages/vue/appearance/Update.vue.stub',
                'resources/js/pages/session/Create.vue' => 'auth/pages/vue/session/Create.vue.stub',
                'resources/js/pages/user/Create.vue' => 'auth/pages/vue/user/Create.vue.stub',
                'resources/js/pages/user-email-reset-notification/Create.vue' => 'auth/pages/vue/user-email-reset-notification/Create.vue.stub',
                'resources/js/pages/user-email-verification-notification/Create.vue' => 'auth/pages/vue/user-email-verification-notification/Create.vue.stub',
                'resources/js/pages/user-password/Create.vue' => 'auth/pages/vue/user-password/Create.vue.stub',
                'resources/js/pages/user-password/Edit.vue' => 'auth/pages/vue/user-password/Edit.vue.stub',
                'resources/js/pages/user-password-confirmation/Create.vue' => 'auth/pages/vue/user-password-confirmation/Create.vue.stub',
                'resources/js/pages/user-profile/Edit.vue' => 'auth/pages/vue/user-profile/Edit.vue.stub',
                'resources/js/pages/user-two-factor-authentication/Show.vue' => 'auth/pages/vue/user-two-factor-authentication/Show.vue.stub',
                'resources/js/pages/user-two-factor-authentication-challenge/Show.vue' => 'auth/pages/vue/user-two-factor-authentication-challenge/Show.vue.stub',
            ],
            FrontendStack::None => [],
        };

        if (! $this->hasExistingSettingsRoutes($context)) {
            return $pages;
        }

        unset(
            $pages['resources/js/pages/appearance/update.tsx'],
            $pages['resources/js/pages/user-password/edit.tsx'],
            $pages['resources/js/pages/user-profile/edit.tsx'],
            $pages['resources/js/pages/appearance/Update.vue'],
            $pages['resources/js/pages/user-password/Edit.vue'],
            $pages['resources/js/pages/user-profile/Edit.vue'],
        );

        return $pages;
    }

    private function hasExistingSettingsRoutes(InstallContext $context): bool
    {
        return $this->files->exists($context->basePath.'/routes/settings.php')
            || $this->files->isDirectory($context->basePath.'/resources/js/pages/settings');
    }
}
