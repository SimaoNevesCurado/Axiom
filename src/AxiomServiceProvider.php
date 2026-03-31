<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom;

use SimaoCurado\Axiom\Commands\AxiomCommand;
use SimaoCurado\Axiom\Commands\MakeActionCommand;
use SimaoCurado\Axiom\Commands\MakeCrudActionCommand;
use SimaoCurado\Axiom\Commands\MakeDtoCommand;
use SimaoCurado\Axiom\Commands\MakeEnumCommand;
use SimaoCurado\Axiom\Commands\MakeRequestCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class AxiomServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('axiom')
            ->hasCommands([
                AxiomCommand::class,
                MakeActionCommand::class,
                MakeCrudActionCommand::class,
                MakeDtoCommand::class,
                MakeEnumCommand::class,
                MakeRequestCommand::class,
            ]);
    }
}
