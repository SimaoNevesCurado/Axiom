<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Enums;

enum FrontendStack: string
{
    case None = 'none';
    case React = 'react';
    case Vue = 'vue';
}
