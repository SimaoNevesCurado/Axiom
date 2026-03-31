<?php

it('exposes an opinionated package description', function () {
    expect((new \SimaoCurado\LaravelExtra\LaravelExtra())::class)
        ->toBe(\SimaoCurado\LaravelExtra\LaravelExtra::class);
});
