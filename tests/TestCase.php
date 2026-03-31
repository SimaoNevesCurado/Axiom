<?php

declare(strict_types=1);

namespace SimaoCurado\LaravelExtra\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SimaoCurado\LaravelExtra\LaravelExtraServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelExtraServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}
