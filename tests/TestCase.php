<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SimaoCurado\Axiom\AxiomServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            AxiomServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}
