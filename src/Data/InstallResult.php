<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Data;

final readonly class InstallResult
{
    /**
     * @param  list<string>  $written
     * @param  list<string>  $skipped
     * @param  list<string>  $planned
     * @param  list<InstallStepResult>  $steps
     * @param  list<InstallChange>  $changes
     */
    public function __construct(
        public array $written,
        public array $skipped,
        public array $planned = [],
        public array $steps = [],
        public array $changes = [],
    ) {}

    public function changed(): bool
    {
        return $this->written !== [];
    }

    public function skippedAnything(): bool
    {
        return $this->skipped !== [];
    }

    public function plannedAnything(): bool
    {
        return $this->planned !== [];
    }
}
