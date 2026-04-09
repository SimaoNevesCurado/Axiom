<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Data;

use SimaoCurado\Axiom\Enums\FrontendStack;

final readonly class FrontendStackDetectionResult
{
    public function __construct(
        public FrontendStack $stack,
        public string $reason,
    ) {}
}
