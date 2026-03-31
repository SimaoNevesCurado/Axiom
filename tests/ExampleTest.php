<?php

use SimaoCurado\LaravelExtra\LaravelExtra;

it('exposes an opinionated package description', function () {
    expect((new LaravelExtra)::class)
        ->toBe(LaravelExtra::class);
});
