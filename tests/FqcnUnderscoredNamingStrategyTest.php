<?php

use M2rius\DartTransformer\Naming\FqcnUnderscoredNamingStrategy;

it('transforms FQCN to underscored name without leading backslash', function () {
    $s = new FqcnUnderscoredNamingStrategy;

    expect($s->transform('App\\Models\\User'))
        ->toBe('App_Models_User');

    expect($s->transform('\\Root\\Namespaced\\ClassName'))
        ->toBe('Root_Namespaced_ClassName');
});

it('handles single-segment class names unchanged', function () {
    $s = new FqcnUnderscoredNamingStrategy;
    expect($s->transform('Plain'))->toBe('Plain');
});
