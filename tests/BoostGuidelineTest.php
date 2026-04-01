<?php

it('ships a Laravel Boost guideline for third-party package integration', function () {
    $path = __DIR__.'/../resources/boost/guidelines/core.blade.php';

    expect($path)->toBeFile()
        ->and(file_get_contents($path))
        ->toContain('## Axiom')
        ->toContain('app/Actions')
        ->toContain('php artisan make:action')
        ->toContain('php artisan make:dto')
        ->toContain('Laravel Boost');
});
