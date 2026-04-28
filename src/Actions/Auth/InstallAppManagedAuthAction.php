<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Actions\Auth;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class InstallAppManagedAuthAction
{
    private DetectFortifyAction $detectFortify;

    private PublishAuthActionsAction $publishActions;

    private PublishAuthControllersAction $publishControllers;

    private CleanLegacyFortifyAuthAction $cleanLegacyFortifyAuth;

    private PublishAuthPagesAction $publishPages;

    private PublishAuthRequestsAction $publishRequests;

    private PublishAuthRoutesAction $publishRoutes;

    private PublishAuthRulesAction $publishRules;

    private PublishAuthTestsAction $publishTests;

    private PublishFortifyScaffoldAction $publishFortifyScaffold;

    private RepairPublishedAuthPagesAction $repairPublishedAuthPages;

    public function __construct(Filesystem $files)
    {
        $this->detectFortify = new DetectFortifyAction($files);
        $this->cleanLegacyFortifyAuth = new CleanLegacyFortifyAuthAction($files);
        $this->publishActions = new PublishAuthActionsAction($files);
        $this->publishControllers = new PublishAuthControllersAction($files);
        $this->publishPages = new PublishAuthPagesAction($files);
        $this->publishRequests = new PublishAuthRequestsAction($files);
        $this->publishRoutes = new PublishAuthRoutesAction($files);
        $this->publishRules = new PublishAuthRulesAction($files);
        $this->publishTests = new PublishAuthTestsAction($files);
        $this->publishFortifyScaffold = new PublishFortifyScaffoldAction($files);
        $this->repairPublishedAuthPages = new RepairPublishedAuthPagesAction($files);
    }

    public function handle(InstallContext $context): void
    {
        if ($this->detectFortify->handle($context->basePath)) {
            $this->publishFortifyScaffold->handle($context);
        }

        $this->cleanLegacyFortifyAuth->handle($context);
        $this->publishActions->handle($context);
        $this->publishRequests->handle($context);
        $this->publishRules->handle($context);
        $this->publishControllers->handle($context);
        $this->publishPages->handle($context);
        $this->repairPublishedAuthPages->handle($context);
        $this->publishTests->handle($context);
        $this->publishRoutes->handle($context);
    }
}
