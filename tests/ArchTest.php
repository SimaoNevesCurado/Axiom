<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('package classes are final')
    ->expect('SimaoCurado\\Axiom')
    ->classes()
    ->toBeFinal();
