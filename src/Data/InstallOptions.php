<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Data;

use Illuminate\Console\Command;

final readonly class InstallOptions
{
    public function __construct(
        public ?string $ai,
        public bool $skills,
        public bool $fortify,
        public ?string $authRoutes,
        public bool $installAuth,
        public bool $ssr,
        public bool $actions,
        public bool $quality,
        public bool $strict,
        public bool $scripts,
        public bool $phpDeps,
        public bool $frontendDeps,
        public bool $phpstan,
        public bool $rector,
        public bool $pint,
        public bool $typeCoverage,
        public ?string $debugTool,
        public bool $oxlint,
        public bool $prettier,
        public bool $concurrently,
        public bool $ncu,
        public bool $force,
        public bool $dryRun,
        public bool $interactive,
    ) {}

    public static function fromCommand(Command $command, bool $interactive): self
    {
        return new self(
            ai: self::stringOption($command->option('ai')),
            skills: (bool) $command->option('skills'),
            fortify: (bool) $command->option('fortify'),
            authRoutes: self::stringOption($command->option('auth-routes')),
            installAuth: (bool) $command->option('install-auth'),
            ssr: (bool) $command->option('ssr'),
            actions: (bool) $command->option('actions'),
            quality: (bool) $command->option('quality'),
            strict: (bool) $command->option('strict'),
            scripts: (bool) $command->option('scripts'),
            phpDeps: (bool) $command->option('php-deps'),
            frontendDeps: (bool) $command->option('frontend-deps'),
            phpstan: (bool) $command->option('phpstan'),
            rector: (bool) $command->option('rector'),
            pint: (bool) $command->option('pint'),
            typeCoverage: (bool) $command->option('type-coverage'),
            debugTool: self::stringOption($command->option('debug-tool')),
            oxlint: (bool) $command->option('oxlint'),
            prettier: (bool) $command->option('prettier'),
            concurrently: (bool) $command->option('concurrently'),
            ncu: (bool) $command->option('ncu'),
            force: (bool) $command->option('force'),
            dryRun: (bool) $command->option('dry-run'),
            interactive: $interactive,
        );
    }

    private static function stringOption(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
