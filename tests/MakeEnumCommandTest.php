<?php

use Illuminate\Support\Str;

it('creates a string backed enum in the host project', function () {
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/app', 0777, true);

    app()->setBasePath($basePath);

    try {
        $this->artisan('make:enum', [
            'name' => 'OrderStatus',
        ])->assertExitCode(0);

        $path = $basePath.'/app/Enums/OrderStatus.php';

        expect($path)->toBeFile()
            ->and(file_get_contents($path))
            ->toContain('namespace App\\Enums;')
            ->toContain('enum OrderStatus: string')
            ->toContain("case Example = 'example';");
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForMakeEnumCommandTest($basePath);
    }
});

it('creates nested enums', function () {
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/app', 0777, true);

    app()->setBasePath($basePath);

    try {
        $this->artisan('make:enum', [
            'name' => 'Billing/InvoiceStatus',
        ])->assertExitCode(0);

        $path = $basePath.'/app/Enums/Billing/InvoiceStatus.php';

        expect($path)->toBeFile()
            ->and(file_get_contents($path))
            ->toContain('namespace App\\Enums\\Billing;')
            ->toContain('enum InvoiceStatus: string');
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForMakeEnumCommandTest($basePath);
    }
});

it('creates an int backed enum when requested', function () {
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/app', 0777, true);

    app()->setBasePath($basePath);

    try {
        $this->artisan('make:enum', [
            'name' => 'Priority',
            '--int' => true,
        ])->assertExitCode(0);

        $contents = (string) file_get_contents($basePath.'/app/Enums/Priority.php');

        expect($contents)
            ->toContain('enum Priority: int')
            ->toContain('case Example = 1;');
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForMakeEnumCommandTest($basePath);
    }
});

function deleteDirectoryForMakeEnumCommandTest(string $path): void
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
            deleteDirectoryForMakeEnumCommandTest($itemPath);

            continue;
        }

        unlink($itemPath);
    }

    rmdir($path);
}
