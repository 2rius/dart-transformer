<?php

use M2rius\DartTransformer\DartTransformer;
use M2rius\DartTransformer\Formatters\DartFormatter;

function dartCliAvailable(): bool
{
    $code = 127;
    @exec('dart --version', $_, $code);

    return $code === 0;
}

it('formats generated file with dart format when available', function () {
    $config = [
        'output_file' => 'tests/dart/fmt_int.dart',
        'formatter' => DartFormatter::class,
    ];

    $t = new DartTransformer($config);
    $result = $t->generate([]);

    expect($result['path'])->toBe('tests/dart/fmt_int.dart');
    expect(file_exists('tests/dart/fmt_int.dart'))->toBeTrue();

    // cleanup
    unlink($result['path']);
    @rmdir('tests/dart');
})->skip(fn () => ! dartCliAvailable(), 'Dart CLI is not available');
