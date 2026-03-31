<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Data;

final readonly class InstallResult
{
    /**
     * @param  list<string>  $written
     * @param  list<string>  $skipped
     */
    public function __construct(
        public array $written,
        public array $skipped,
    ) {}
}
