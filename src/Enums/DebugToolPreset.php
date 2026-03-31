<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Enums;

enum DebugToolPreset: string
{
    case None = 'none';
    case Debugbar = 'debugbar';
    case Telescope = 'telescope';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::None->value => 'Skip for now',
            self::Debugbar->value => 'Debugbar',
            self::Telescope->value => 'Telescope',
        ];
    }
}
