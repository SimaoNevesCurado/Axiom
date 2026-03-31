<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Support;

final readonly class ClassName
{
    public function __construct(
        public string $name,
        public string $namespace,
        public string $path,
    ) {}
}
