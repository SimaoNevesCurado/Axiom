<?php

declare(strict_types=1);

namespace SimaoCurado\LaravelExtra;

use SimaoCurado\LaravelExtra\Commands\LaravelExtraCommand;
use SimaoCurado\LaravelExtra\Commands\MakeActionCommand;
use SimaoCurado\LaravelExtra\Commands\MakeCrudActionCommand;
use SimaoCurado\LaravelExtra\Commands\MakeDtoCommand;
use SimaoCurado\LaravelExtra\Commands\MakeEnumCommand;
use SimaoCurado\LaravelExtra\Commands\MakeRequestCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class LaravelExtraServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-extra')
            ->hasCommands([
                LaravelExtraCommand::class,
                MakeActionCommand::class,
                MakeCrudActionCommand::class,
                MakeDtoCommand::class,
                MakeEnumCommand::class,
                MakeRequestCommand::class,
            ]);
    }
}
