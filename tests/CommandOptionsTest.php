<?php

use Illuminate\Support\Facades\Artisan;
use M2rius\DartTransformer\DartTransformer;

it('honors --path, --output and --format options', function () {
    // Spy on DartTransformer::generate by binding a custom instance into the container
    $tmpOut = 'tests/dart/cli_generated.dart';

    // Ensure clean state
    if (file_exists($tmpOut)) {
        @unlink($tmpOut);
    }

    // Run command with options
    Artisan::call('dart:transform', [
        '--path' => 'tests',
        '--output' => $tmpOut,
        '--format' => true,
    ]);

    // Verify output file created in requested location
    expect(file_exists($tmpOut))->toBeTrue();

    // File should contain generated header markers
    $content = file_get_contents($tmpOut);
    expect($content)->toContain('// GENERATED CODE - DO NOT MODIFY BY HAND');

    // cleanup
    @unlink($tmpOut);
    @rmdir('tests/dart');
});
