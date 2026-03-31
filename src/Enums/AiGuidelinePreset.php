<?php

declare(strict_types=1);

namespace SimaoCurado\LaravelExtra\Enums;

enum AiGuidelinePreset: string
{
    case Boost = 'boost';
    case Codex = 'codex';
    case Claude = 'claude';
    case None = 'none';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Boost->value => 'Boost preset',
            self::Codex->value => 'Codex preset',
            self::Claude->value => 'Claude preset',
            self::None->value => 'Skip for now',
        ];
    }
}
