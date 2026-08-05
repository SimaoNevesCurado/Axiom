<?php

declare(strict_types=1);

namespace SimaoCurado\Axiom\Support;

use Illuminate\Filesystem\Filesystem;
use SimaoCurado\Axiom\Data\InstallChange;
use SimaoCurado\Axiom\Data\InstallResult;
use SimaoCurado\Axiom\Data\InstallSelections;
use SimaoCurado\Axiom\Data\InstallStepResult;

final class InstallContext
{
    /**
     * @var list<string>
     */
    public array $written = [];

    /**
     * @var list<string>
     */
    public array $skipped = [];

    /**
     * @var list<string>
     */
    public array $planned = [];

    /**
     * @var list<InstallStepResult>
     */
    public array $steps = [];

    /**
     * @var list<InstallChange>
     */
    public array $changes = [];

    public ?string $currentStep = null;

    public function __construct(
        public readonly InstallSelections $selections,
        public readonly string $basePath,
    ) {}

    public function relativePath(string $path): string
    {
        return ltrim(str_replace($this->basePath, '', $path), '/');
    }

    public function recordWritten(string $path): void
    {
        $this->appendUnique($this->written, $path);
        $this->recordChange($path, 'written');
    }

    public function recordPlanned(string $path, ?string $reason = null): void
    {
        $this->appendUnique($this->planned, $path);
        $this->recordChange($path, 'planned', $reason);
    }

    public function recordSkipped(string $path, ?string $reason = null): void
    {
        if (in_array($path, $this->written, true)) {
            return;
        }

        $this->appendUnique($this->skipped, $path);
        $this->recordChange($path, 'skipped', $reason);
    }

    public function result(): InstallResult
    {
        return new InstallResult($this->written, $this->skipped, $this->planned, $this->steps, $this->changes);
    }

    public function recordStepResult(string $label, int $written, int $skipped, int $planned): void
    {
        $this->steps[] = new InstallStepResult($label, $written, $skipped, $planned);
    }

    public function recordChange(string $path, string $status, ?string $reason = null): void
    {
        $this->changes[] = new InstallChange($path, $status, $reason, $this->currentStep);
    }

    public function putFile(Filesystem $files, string $relativePath, string $content): void
    {
        if ($this->selections->dryRun) {
            $this->recordPlanned($relativePath, 'Dry run: file would be written.');

            return;
        }

        $path = $this->basePath.'/'.$relativePath;
        $directory = dirname($path);

        if (! $files->isDirectory($directory)) {
            $files->makeDirectory($directory, 0755, true);
        }

        $files->put($path, $content);
        $this->recordWritten($relativePath);
    }

    public function deletePath(Filesystem $files, string $relativePath): void
    {
        if ($this->selections->dryRun) {
            $this->recordPlanned($relativePath, 'Dry run: path would be deleted.');

            return;
        }

        $path = $this->basePath.'/'.$relativePath;

        if ($files->isDirectory($path)) {
            $files->deleteDirectory($path);
        } else {
            $files->delete($path);
        }

        $this->recordWritten($relativePath);
    }

    /**
     * @param  list<string>  $items
     */
    private function appendUnique(array &$items, string $value): void
    {
        if (! in_array($value, $items, true)) {
            $items[] = $value;
        }
    }
}
