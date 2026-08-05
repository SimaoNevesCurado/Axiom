<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Commands;

use Closure;
use Illuminate\Console\Command;
use InvalidArgumentException;
use SimaoCurado\Axiom\Actions\InstallAxiomAction;
use SimaoCurado\Axiom\Actions\ResolveInstallSelectionsAction;
use SimaoCurado\Axiom\Concerns\ConfiguresPrompts;
use SimaoCurado\Axiom\Data\InstallChange;
use SimaoCurado\Axiom\Data\InstallOptions;
use SimaoCurado\Axiom\Data\InstallResult;
use SimaoCurado\Axiom\Data\InstallStepResult;
use SimaoCurado\Axiom\Exceptions\InstallStepFailedException;
use SimaoCurado\Axiom\Support\ProjectDetector;

use function Laravel\Prompts\task;

final class AxiomCommand extends Command
{
    use ConfiguresPrompts;

    protected $signature = 'axiom:install
        {--ai= : AI guideline preset (boost, codex, claude, gemini, opencode, none)}
        {--skills : Install Axiom AI skills into .ai/skills}
        {--fortify : [Deprecated] Use Fortify routes when laravel/fortify exists}
        {--auth-routes= : Auth routes mode (app, fortify)}
        {--install-auth : Install Axiom auth scaffold when the project has no auth scaffold}
        {--ssr : Use SSR in frontend starter kits}
        {--actions : Install action-oriented architecture guidance}
        {--quality : Install quality and tooling guidance}
        {--strict : Install strict Laravel defaults config}
        {--scripts : Install opinionated composer scripts into the host project}
        {--php-deps : Add recommended PHP quality dev dependencies to composer.json}
        {--frontend-deps : Add recommended frontend quality dev dependencies to package.json}
        {--phpstan : Add PHPStan and Larastan to composer.json}
        {--rector : Add Rector to composer.json}
        {--pint : Add Laravel Pint to composer.json}
        {--type-coverage : Add Pest type coverage to composer.json}
        {--debug-tool= : Debug tool preset (none, debugbar, telescope)}
        {--oxlint : Add Oxlint to package.json}
        {--prettier : Add Prettier and plugins to package.json}
        {--concurrently : Add concurrently to package.json}
        {--ncu : Add npm-check-updates to package.json}
        {--dry-run : Show what would change without writing files}
        {--json : Output a machine-readable JSON result}
        {--force : Overwrite existing files}';

    protected $description = 'Install opinionated Axiom presets into the host application';

    public function handle(InstallAxiomAction $installAxiom, ResolveInstallSelectionsAction $resolveInstallSelections): int
    {
        $this->configurePromptFallbacks($this->input, $this->output);
        $warnings = [];

        if (! $this->wantsJson()) {
            $this->renderBanner();
        }

        try {
            $selections = $resolveInstallSelections->handle(
                InstallOptions::fromCommand($this, $this->input->isInteractive()),
                new ProjectDetector,
                function (string $message) use (&$warnings): void {
                    $warnings[] = $message;

                    if (! $this->wantsJson()) {
                        $this->components->warn($message);
                    }
                },
            );
        } catch (InvalidArgumentException $exception) {
            if ($this->wantsJson()) {
                $this->writeJson([
                    'success' => false,
                    'error' => $exception->getMessage(),
                ]);

                return self::FAILURE;
            }

            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        try {
            $result = $installAxiom->handle($selections, base_path(), $this->stepRunner());
        } catch (InstallStepFailedException $exception) {
            if ($this->wantsJson()) {
                $this->writeJson([
                    'success' => false,
                    'dryRun' => $selections->dryRun,
                    'error' => $exception->getMessage(),
                    'step' => $exception->step,
                    'previous' => $exception->getPrevious()?->getMessage(),
                    'warnings' => $warnings,
                ]);

                return self::FAILURE;
            }

            $this->components->error($exception->getMessage());

            if ($exception->getPrevious() !== null) {
                $this->line('  '.$exception->getPrevious()->getMessage());
            }

            return self::FAILURE;
        }

        if ($this->wantsJson()) {
            $this->writeJson($this->jsonResult($result, $selections->dryRun, $warnings));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->info($selections->dryRun ? 'Axiom dry run finished.' : 'Axiom installer finished.');

        if ($result->steps !== []) {
            $this->line('  Step summary:');

            foreach ($result->steps as $step) {
                $this->line(sprintf(
                    '  <fg=gray>•</> %s: %d changed, %d planned, %d skipped',
                    $step->label,
                    $step->written,
                    $step->planned,
                    $step->skipped,
                ));
            }
        }

        if ($result->plannedAnything()) {
            $this->newLine();
            $this->line('  Would create or update:');

            foreach ($result->planned as $path) {
                $this->line("  <fg=cyan>•</> {$path}");
            }
        }

        if ($result->changed()) {
            $this->newLine();
            $this->line('  Created or updated:');

            foreach ($result->written as $path) {
                $this->line("  <fg=green>•</> {$path}");
            }
        }

        if ($result->skippedAnything()) {
            $this->newLine();
            $this->line('  Skipped:');

            foreach ($result->skipped as $path) {
                $reason = $this->skippedReason($result->changes, $path);

                $this->line("  <fg=yellow>•</> {$path}".($reason === null ? '' : " ({$reason})"));
            }
        }

        if (! $result->changed() && ! $result->plannedAnything() && ! $result->skippedAnything()) {
            $this->line('  Nothing changed. Selected presets are already up to date or not applicable.');
        }

        $this->newLine();
        $this->line('  Next steps:');

        if ($selections->dryRun) {
            $this->line('  • Re-run without `--dry-run` to apply these changes.');
        } elseif (in_array('composer.json', $result->written, true)) {
            $this->line('  • Run `composer update` to sync new PHP dependencies and update composer.lock.');
        } else {
            $this->line('  • Run `composer install` if you still need to install PHP dependencies.');
        }

        if ($selections->dryRun) {
            // The dry-run message above is enough; dependency install commands only apply after writes.
        } elseif (in_array('package.json', $result->written, true)) {
            $this->line('  • Run `bun install` to sync new frontend dependencies.');
        } elseif (file_exists(base_path('package.json'))) {
            $this->line('  • Run `bun install` if you still need to install frontend dependencies.');
        }

        $this->line('  • Review `AGENTS.md`, `CLAUDE.md`, `GEMINI.md`, `OPENCODE.md`, and `.ai/skills/*` if you installed AI guidance.');

        return self::SUCCESS;
    }

    private function renderBanner(): void
    {
        if (! $this->input->isInteractive()) {
            return;
        }

        $this->line("\033[34m");
        $this->line(' █████╗ ██╗  ██╗██╗ ██████╗ ███╗   ███╗');
        $this->line('██╔══██╗╚██╗██╔╝██║██╔═══██╗████╗ ████║');
        $this->line('███████║ ╚███╔╝ ██║██║   ██║██╔████╔██║');
        $this->line('██╔══██║ ██╔██╗ ██║██║   ██║██║╚██╔╝██║');
        $this->line('██║  ██║██╔╝ ██╗██║╚██████╔╝██║ ╚═╝ ██║');
        $this->line('╚═╝  ╚═╝╚═╝  ╚═╝╚═╝ ╚═════╝ ╚═╝     ╚═╝');
        $this->line("\033[0m");
    }

    /**
     * @return callable(string, Closure(): void): void
     */
    private function stepRunner(): callable
    {
        return function (string $label, Closure $run): void {
            if ($this->wantsJson()) {
                $run();

                return;
            }

            if ($this->input->isInteractive()) {
                task($label, $run);

                return;
            }

            $this->line("  - {$label}");
            $run();
        };
    }

    /**
     * @param  list<InstallChange>  $changes
     */
    private function skippedReason(array $changes, string $path): ?string
    {
        foreach ($changes as $change) {
            if ($change->path === $path && $change->status === 'skipped') {
                return $change->reason;
            }
        }

        return null;
    }

    private function wantsJson(): bool
    {
        return (bool) $this->option('json');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeJson(array $payload): void
    {
        $this->output->writeln((string) json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    private function jsonResult(InstallResult $result, bool $dryRun, array $warnings): array
    {
        return [
            'success' => true,
            'dryRun' => $dryRun,
            'written' => $result->written,
            'planned' => $result->planned,
            'skipped' => $result->skipped,
            'steps' => array_map(
                static fn (InstallStepResult $step): array => [
                    'label' => $step->label,
                    'written' => $step->written,
                    'planned' => $step->planned,
                    'skipped' => $step->skipped,
                ],
                $result->steps,
            ),
            'changes' => array_map(
                static fn (InstallChange $change): array => [
                    'path' => $change->path,
                    'status' => $change->status,
                    'reason' => $change->reason,
                    'step' => $change->step,
                ],
                $result->changes,
            ),
            'warnings' => $warnings,
        ];
    }
}
