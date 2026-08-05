<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Data;

use Closure;
use SimaoCurado\Axiom\Support\InstallContext;

final readonly class InstallStep
{
    /**
     * @param  Closure(InstallContext): void  $run
     */
    public function __construct(
        public string $label,
        private Closure $run,
    ) {}

    public function run(InstallContext $context): void
    {
        ($this->run)($context);
    }
}
