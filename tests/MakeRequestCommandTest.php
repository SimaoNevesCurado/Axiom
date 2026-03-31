<?php

use Illuminate\Support\Str;

it('creates a form request in the host project', function () {
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/app', 0777, true);

    app()->setBasePath($basePath);

    try {
        $this->artisan('make:request', [
            'name' => 'CreateUserRequest',
        ])->assertExitCode(0);

        $path = $basePath.'/app/Http/Requests/CreateUserRequest.php';

        expect($path)->toBeFile()
            ->and(file_get_contents($path))
            ->toContain('namespace App\\Http\\Requests;')
            ->toContain('extends FormRequest')
            ->toContain('public function rules(): array');
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForMakeRequestCommandTest($basePath);
    }
});

function deleteDirectoryForMakeRequestCommandTest(string $path): void
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
            deleteDirectoryForMakeRequestCommandTest($itemPath);

            continue;
        }

        unlink($itemPath);
    }

    rmdir($path);
}
