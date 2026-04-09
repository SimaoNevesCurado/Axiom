<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Enums;

enum FrontendStack: string
{
    case InertiaVue = 'inertia-vue';
    case InertiaReact = 'inertia-react';
    case Blade = 'blade';
}
