<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Data;

final readonly class InstallChange
{
    public function __construct(
        public string $path,
        public string $status,
        public ?string $reason,
        public ?string $step,
    ) {}
}
