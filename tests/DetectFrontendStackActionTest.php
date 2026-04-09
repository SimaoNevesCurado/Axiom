<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use SimaoCurado\Axiom\Actions\DetectFrontendStackAction;
use SimaoCurado\Axiom\Enums\FrontendStack;

it('detects inertia vue projects from package json', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();
    mkdir($basePath, 0777, true);

    file_put_contents($basePath.'/package.json', json_encode([
        'dependencies' => [
            '@inertiajs/vue3' => '^2.0.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

    $result = (new DetectFrontendStackAction(new Filesystem))->handle($basePath);

    expect($result->stack)->toBe(FrontendStack::InertiaVue);

    deleteDirectoryForDetectFrontendStackActionTest($basePath);
});

it('detects inertia react projects from package json', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();
    mkdir($basePath, 0777, true);

    file_put_contents($basePath.'/package.json', json_encode([
        'dependencies' => [
            '@inertiajs/react' => '^2.0.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

    $result = (new DetectFrontendStackAction(new Filesystem))->handle($basePath);

    expect($result->stack)->toBe(FrontendStack::InertiaReact);

    deleteDirectoryForDetectFrontendStackActionTest($basePath);
});

it('falls back to blade when inertia dependencies are missing', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();
    mkdir($basePath, 0777, true);

    file_put_contents($basePath.'/package.json', json_encode([
        'dependencies' => [
            'alpinejs' => '^3.0.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

    $result = (new DetectFrontendStackAction(new Filesystem))->handle($basePath);

    expect($result->stack)->toBe(FrontendStack::Blade);

    deleteDirectoryForDetectFrontendStackActionTest($basePath);
});

it('falls back to blade when package json is missing', function () {
    $basePath = sys_get_temp_dir().'/axiom-'.Str::uuid();
    mkdir($basePath, 0777, true);

    $result = (new DetectFrontendStackAction(new Filesystem))->handle($basePath);

    expect($result->stack)->toBe(FrontendStack::Blade);

    deleteDirectoryForDetectFrontendStackActionTest($basePath);
});

function deleteDirectoryForDetectFrontendStackActionTest(string $path): void
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
            deleteDirectoryForDetectFrontendStackActionTest($itemPath);

            continue;
        }

        unlink($itemPath);
    }

    rmdir($path);
}
