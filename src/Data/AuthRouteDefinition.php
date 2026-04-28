<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Data;

use SimaoCurado\Axiom\Enums\AuthRouteMiddleware;

final readonly class AuthRouteDefinition
{
    public function __construct(
        public ?string $name,
        public AuthRouteMiddleware $middleware,
        public string $method,
        public string $uri,
        public string $code,
    ) {}
}
