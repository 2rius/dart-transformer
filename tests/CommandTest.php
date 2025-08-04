<?php

use Spatie\LaravelData\Data;

// Test Data class for command testing
class CommandTestUserData extends Data
{
    public function __construct(
        public int $id,
        public string $name
    ) {}
}

it('can run dart transform command without arguments', function () {
    $this->artisan('dart:transform')
        ->expectsOutput('Please specify a class to transform or use --discover to transform all applicable classes')
        ->assertExitCode(0);
});

it('can transform a specific class via command', function () {
    // Clean up any existing files
    $outputFile = 'resources/dart/generated.dart';
    if (file_exists($outputFile)) {
        unlink($outputFile);
    }

    $this->artisan('dart:transform', ['class' => CommandTestUserData::class])
        ->expectsOutput('Transforming '.CommandTestUserData::class.'...')
        ->expectsOutput('✅ Successfully transformed '.CommandTestUserData::class.' to consolidated file')
        ->assertExitCode(0);

    // Verify file was created
    expect(file_exists($outputFile))->toBeTrue();

    // Clean up
    if (file_exists($outputFile)) {
        unlink($outputFile);
    }
    if (is_dir('resources/dart')) {
        $files = glob('resources/dart/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir('resources/dart');
    }
});

it('can transform a specific class in separate mode', function () {
    // Clean up any existing files
    $outputFile = 'resources/dart/command_test_user_data.dart';
    if (file_exists($outputFile)) {
        unlink($outputFile);
    }

    $this->artisan('dart:transform', [
        'class' => CommandTestUserData::class,
        '--mode' => 'separate',
    ])
        ->expectsOutput('Transforming '.CommandTestUserData::class.'...')
        ->expectsOutput('✅ Successfully transformed '.CommandTestUserData::class)
        ->assertExitCode(0);

    // Verify file was created
    expect(file_exists($outputFile))->toBeTrue();

    // Clean up
    if (file_exists($outputFile)) {
        unlink($outputFile);
    }
    if (is_dir('resources/dart')) {
        $files = glob('resources/dart/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir('resources/dart');
    }
});

it('shows error for non-existent class', function () {
    $this->artisan('dart:transform', ['class' => 'App\\NonExistentClass'])
        ->expectsOutput('Class App\\NonExistentClass does not exist')
        ->assertExitCode(1);
});

it('can run discovery mode in single file mode', function () {
    // Discovery mode with custom output to avoid conflicts
    $this->artisan('dart:transform', [
        '--discover' => true,
        '--output' => 'tests/command_output',
        '--filename' => 'discovered.dart',
    ])
        ->expectsOutput('Discovering and transforming classes...')
        ->assertExitCode(0);

    // Clean up any generated files
    if (file_exists('tests/command_output/discovered.dart')) {
        unlink('tests/command_output/discovered.dart');
    }
    if (is_dir('tests/command_output')) {
        rmdir('tests/command_output');
    }
});
