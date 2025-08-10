<?php

use M2rius\DartTransformer\DartTransformer;
use M2rius\DartTransformer\Transformers\EnumTransformer;

enum ModeTestStatus: string
{
    case ACTIVE = 'active';
}

it('respects transform_to_native_enums config', function () {
    $config = [
        'output_file' => 'tests/dart/mode_generated.dart',
        'transformers' => [
            'enums' => EnumTransformer::class,
        ],
    ];

    // Native
    $t1 = new DartTransformer($config + ['transform_to_native_enums' => true]);
    $r1 = $t1->generate([ModeTestStatus::class]);
    $c1 = file_get_contents($r1['path']);
    expect($c1)->toContain('enum ModeTestStatus');
    unlink($r1['path']);

    // String constants
    $t2 = new DartTransformer($config + ['transform_to_native_enums' => false]);
    $r2 = $t2->generate([ModeTestStatus::class]);
    $c2 = file_get_contents($r2['path']);
    expect($c2)->toContain('class ModeTestStatus');
    unlink($r2['path']);

    if (is_dir('tests/dart')) {
        @rmdir('tests/dart');
    }
});
