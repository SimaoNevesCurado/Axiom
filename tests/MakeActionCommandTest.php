<?php

use Illuminate\Support\Str;

it('creates an action class in the host project', function () {
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/app', 0777, true);

    app()->setBasePath($basePath);

    try {
        $this->artisan('make:action', [
            'name' => 'CreateInvoice',
        ])->assertExitCode(0);

        $path = $basePath.'/app/Actions/CreateInvoice.php';

        expect($path)->toBeFile()
            ->and(file_get_contents($path))
            ->toContain('namespace App\\Actions;')
            ->toContain('final readonly class CreateInvoice')
            ->toContain('public function handle(): void');
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForMakeActionCommandTest($basePath);
    }
});

it('creates nested action classes', function () {
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/app', 0777, true);

    app()->setBasePath($basePath);

    try {
        $this->artisan('make:action', [
            'name' => 'Billing/SyncInvoices',
        ])->assertExitCode(0);

        $path = $basePath.'/app/Actions/Billing/SyncInvoices.php';

        expect($path)->toBeFile()
            ->and(file_get_contents($path))
            ->toContain('namespace App\\Actions\\Billing;')
            ->toContain('final readonly class SyncInvoices');
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForMakeActionCommandTest($basePath);
    }
});

it('creates an action class with a dto handle signature', function () {
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/app', 0777, true);

    app()->setBasePath($basePath);

    try {
        $this->artisan('make:action', [
            'name' => 'CreateInvoice',
            '--dto' => 'Billing/InvoiceDto',
        ])->assertExitCode(0);

        $contents = (string) file_get_contents($basePath.'/app/Actions/CreateInvoice.php');

        expect($contents)
            ->toContain('use App\\Dto\\Billing\\InvoiceDto;')
            ->toContain('public function handle(InvoiceDto $invoiceDto): void');
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForMakeActionCommandTest($basePath);
    }
});

function deleteDirectoryForMakeActionCommandTest(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    $items = scandir($path);

    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $itemPath = $path.'/'.$item;

        if (is_dir($itemPath)) {
            deleteDirectoryForMakeActionCommandTest($itemPath);

            continue;
        }

        unlink($itemPath);
    }

    rmdir($path);
}
