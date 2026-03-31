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
            self::Boost->value => 'Laravel Boost style guidelines',
            self::Codex->value => 'Codex-friendly AGENTS.md',
            self::Claude->value => 'Claude-friendly CLAUDE.md',
            self::None->value => 'Skip AI guidelines for now',
        ];
    }
}
