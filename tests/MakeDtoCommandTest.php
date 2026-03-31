<?php

use Illuminate\Support\Str;

it('creates a readonly dto class in the host project', function () {
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/app', 0777, true);

    app()->setBasePath($basePath);

    try {
        $this->artisan('make:dto', [
            'name' => 'UserDto',
        ])->assertExitCode(0);

        $path = $basePath.'/app/Dto/UserDto.php';

        expect($path)->toBeFile()
            ->and(file_get_contents($path))
            ->toContain('namespace App\\Dto;')
            ->toContain('final readonly class UserDto')
            ->toContain('public function __construct()')
            ->toContain('public static function fromArray(array $data): self')
            ->toContain('public function toArray(): array')
            ->toContain('return new self();')
            ->toContain('return [];');
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForMakeDtoCommandTest($basePath);
    }
});

it('creates a dto with promoted readonly properties', function () {
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/app', 0777, true);

    app()->setBasePath($basePath);

    try {
        $this->artisan('make:dto', [
            'name' => 'CreateUserDto',
            '--property' => ['name:string', 'age:int'],
        ])->assertExitCode(0);

        $contents = (string) file_get_contents($basePath.'/app/Dto/CreateUserDto.php');

        expect($contents)
            ->toContain('public string $name')
            ->toContain('public int $age')
            ->toContain("name: \$data['name']")
            ->toContain("age: \$data['age']")
            ->toContain("'name' => \$this->name")
            ->toContain("'age' => \$this->age");
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForMakeDtoCommandTest($basePath);
    }
});

it('does not overwrite an existing dto class without force', function () {
    $basePath = sys_get_temp_dir().'/laravel-extra-'.Str::uuid();
    $originalBasePath = base_path();

    mkdir($basePath, 0777, true);
    mkdir($basePath.'/app/Dto', 0777, true);
    file_put_contents($basePath.'/app/Dto/UserDto.php', 'existing');

    app()->setBasePath($basePath);

    try {
        $this->artisan('make:dto', [
            'name' => 'UserDto',
        ])->assertExitCode(1);

        expect(file_get_contents($basePath.'/app/Dto/UserDto.php'))->toBe('existing');
    } finally {
        app()->setBasePath($originalBasePath);
        deleteDirectoryForMakeDtoCommandTest($basePath);
    }
});

function deleteDirectoryForMakeDtoCommandTest(string $path): void
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
            deleteDirectoryForMakeDtoCommandTest($itemPath);

            continue;
        }

        unlink($itemPath);
    }

    rmdir($path);
}
