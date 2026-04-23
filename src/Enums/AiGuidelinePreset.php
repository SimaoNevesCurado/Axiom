<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Enums;

enum AiGuidelinePreset: string
{
    case Boost = 'boost';
    case Codex = 'codex';
    case Claude = 'claude';
    case Gemini = 'gemini';
    case Opencode = 'opencode';
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
            self::Gemini->value => 'Gemini preset',
            self::Opencode->value => 'Opencode preset',
            self::None->value => 'Skip for now',
        ];
    }
}
