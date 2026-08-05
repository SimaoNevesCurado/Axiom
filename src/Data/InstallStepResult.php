<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Data;

final readonly class InstallStepResult
{
    public function __construct(
        public string $label,
        public int $written,
        public int $skipped,
        public int $planned = 0,
    ) {}

    public function changed(): bool
    {
        return $this->written > 0 || $this->planned > 0;
    }
}
