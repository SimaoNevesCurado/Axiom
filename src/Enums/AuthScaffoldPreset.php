<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Enums;

enum AuthScaffoldPreset: string
{
    case Fortify = 'fortify';
    case AppManaged = 'app-managed';

    public static function fromOption(string $value): self
    {
        return match ($value) {
            self::Fortify->value => self::Fortify,
            self::AppManaged->value, 'auth-php' => self::AppManaged,
            default => self::from($value),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Fortify->value => 'Let Fortify handle them',
            self::AppManaged->value => 'Create app-managed auth',
        ];
    }
}
