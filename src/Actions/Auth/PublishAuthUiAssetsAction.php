<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Actions\DetectFrontendStackAction;
use SimaoCurado\Axiom\Actions\ResolveStubPathAction;
use SimaoCurado\Axiom\Actions\WriteFileAction;
use SimaoCurado\Axiom\Enums\FrontendStack;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class PublishAuthUiAssetsAction
{
    private DetectFrontendStackAction $detectFrontendStack;

    private ResolveStubPathAction $stubs;

    private WriteFileAction $writeFile;

    public function __construct(Filesystem $files)
    {
        $this->detectFrontendStack = new DetectFrontendStackAction($files);
        $this->stubs = new ResolveStubPathAction($files);
        $this->writeFile = new WriteFileAction($files);
    }

    public function handle(InstallContext $context): void
    {
        if ($this->detectFrontendStack->handle($context->basePath) !== FrontendStack::Vue) {
            return;
        }

        foreach ($this->uiStubs() as $target => $stub) {
            $this->writeFile->handle($context, $target, $this->stubs->contents($stub));
        }
    }

    /**
     * @return array<string, string>
     */
    private function uiStubs(): array
    {
        return [
            'resources/js/components/AppLogoIcon.vue' => 'auth/ui/components/AppLogoIcon.vue.stub',
            'resources/js/components/InputError.vue' => 'auth/ui/components/InputError.vue.stub',
            'resources/js/components/TextLink.vue' => 'auth/ui/components/TextLink.vue.stub',
            'resources/js/components/ui/button/Button.vue' => 'auth/ui/components/ui/button/Button.vue.stub',
            'resources/js/components/ui/button/index.ts' => 'auth/ui/components/ui/button/index.ts.stub',
            'resources/js/components/ui/checkbox/Checkbox.vue' => 'auth/ui/components/ui/checkbox/Checkbox.vue.stub',
            'resources/js/components/ui/checkbox/index.ts' => 'auth/ui/components/ui/checkbox/index.ts.stub',
            'resources/js/components/ui/input/Input.vue' => 'auth/ui/components/ui/input/Input.vue.stub',
            'resources/js/components/ui/input/index.ts' => 'auth/ui/components/ui/input/index.ts.stub',
            'resources/js/components/ui/label/Label.vue' => 'auth/ui/components/ui/label/Label.vue.stub',
            'resources/js/components/ui/label/index.ts' => 'auth/ui/components/ui/label/index.ts.stub',
            'resources/js/components/ui/spinner/Spinner.vue' => 'auth/ui/components/ui/spinner/Spinner.vue.stub',
            'resources/js/components/ui/spinner/index.ts' => 'auth/ui/components/ui/spinner/index.ts.stub',
            'resources/js/layouts/AuthLayout.vue' => 'auth/ui/layouts/AuthLayout.vue.stub',
            'resources/js/layouts/auth/AuthSimpleLayout.vue' => 'auth/ui/layouts/auth/AuthSimpleLayout.vue.stub',
            'resources/js/lib/utils.ts' => 'auth/ui/lib/utils.ts.stub',
        ];
    }
}
