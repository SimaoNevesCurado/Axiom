<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Exceptions;

use RuntimeException;
use Throwable;

final class InstallStepFailedException extends RuntimeException
{
    public function __construct(
        public readonly string $step,
        Throwable $previous,
    ) {
        parent::__construct(
            sprintf('Axiom installation failed while: %s.', $step),
            previous: $previous,
        );
    }
}
