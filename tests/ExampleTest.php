<?php

use SimaoCurado\Axiom\Axiom;

it('exposes an opinionated package description', function () {
    expect((new Axiom)::class)
        ->toBe(Axiom::class);
});
