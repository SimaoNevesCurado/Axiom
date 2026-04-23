<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Enums;

enum AuthRoutesPreset: string
{
    case AppManaged = 'app';
    case Fortify = 'fortify';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::AppManaged->value => 'App managed (web.php)',
            self::Fortify->value => 'Fortify',
        ];
    }
}
