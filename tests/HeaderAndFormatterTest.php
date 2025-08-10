<?php

use M2rius\DartTransformer\DartTransformer;
use M2rius\DartTransformer\Formatters\Formatter;
use Spatie\LaravelData\Data;

class HeaderTestData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}

class TestFormatter implements Formatter
{
    public function format(string $file): void
    {
        file_put_contents($file, "\n// formatted", FILE_APPEND);
    }
}

it('writes header and runs formatter on generated file', function () {
    $config = [
        'output_file' => 'tests/dart/header_generated.dart',
        'formatter' => TestFormatter::class,
        'dart' => [
            'header' => '/* my header */',
            'use_json_annotation' => true,
        ],
        'transformers' => [
            \M2rius\DartTransformer\Transformers\DataClassTransformer::class,
        ],
    ];

    $transformer = new DartTransformer($config);
    $result = $transformer->generate([HeaderTestData::class]);

    expect($result['path'])->toBe('tests/dart/header_generated.dart');
    $content = file_get_contents($result['path']);

    // Header first
    expect(str_starts_with($content, "/* my header */\n"))->toBeTrue();

    // Generated markers present
    expect($content)->toContain('// GENERATED CODE - DO NOT MODIFY BY HAND');
    expect($content)->toContain('// ignore_for_file: type=lint');

    // Part file derived from output file
    expect($content)->toContain("part 'header_generated.g.dart';");

    // Formatter appended marker
    expect(rtrim($content))->toEndWith('// formatted');

    // cleanup
    unlink($result['path']);
    if (is_dir('tests/dart')) {
        @rmdir('tests/dart');
    }
});
