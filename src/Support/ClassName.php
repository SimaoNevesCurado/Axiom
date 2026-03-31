<?php

declare(strict_types=1);

namespace SimaoCurado\LaravelExtra\Support;

final readonly class ClassName
{
    public function __construct(
        public string $name,
        public string $namespace,
        public string $path,
    ) {}
}
