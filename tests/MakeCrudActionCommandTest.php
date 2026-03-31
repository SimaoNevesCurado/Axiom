<?php

use Illuminate\Support\Str;

it('creates a create crud action with dto support', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/app', 0777, true);

    app()->setBasePath($basePath);

    try {
        $this->artisan('make:crud-action', [
            'model' => 'User',
            '--operation' => 'create',
            '--dto' => 'Users/CreateUserDto',
        ])->assertExitCode(0);

        $path = $basePath.'/app/Actions/CreateUser.php';
        $contents = (string) file_get_contents($path);

        expect($path)->toBeFile()
            ->and($contents)->toContain('use App\\Models\\User;')
            ->toContain('use App\\Dto\\Users\\CreateUserDto;')
            ->toContain('public function handle(CreateUserDto $createUserDto): User')
            ->toContain('return new User();');
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForMakeCrudActionCommandTest($basePath);
    }
});

it('creates a list crud action', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/app', 0777, true);

    app()->setBasePath($basePath);

    try {
        $this->artisan('make:crud-action', [
            'model' => 'User',
            '--operation' => 'list',
        ])->assertExitCode(0);

        $contents = (string) file_get_contents($basePath.'/app/Actions/ListUsers.php');

        expect($contents)
            ->toContain('use Illuminate\\Database\\Eloquent\\Collection;')
            ->toContain('public function handle(): Collection')
            ->toContain('return User::query()->get();');
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForMakeCrudActionCommandTest($basePath);
    }
});

function deleteDirectoryForMakeCrudActionCommandTest(string $path): void
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
            deleteDirectoryForMakeCrudActionCommandTest($itemPath);

            continue;
        }

        unlink($itemPath);
    }

    rmdir($path);
}
