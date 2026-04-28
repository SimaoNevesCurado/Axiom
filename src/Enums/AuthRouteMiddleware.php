<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Enums;

enum AuthRouteMiddleware: string
{
    case Auth = 'auth';
    case Guest = 'guest';
}
