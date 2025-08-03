<?php

use M2rius\DartTransformer\DartTransformer;
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
    // Mock the transformer
    $this->mock(DartTransformer::class, function ($mock) {
        $mock->shouldReceive('transformToFile')
            ->once()
            ->with(CommandTestUserData::class)
            ->andReturn('tests/dart/command_test_user_data.dart');
    });

    $this->artisan('dart:transform', ['class' => CommandTestUserData::class])
        ->expectsOutput('Transforming '.CommandTestUserData::class.'...')
        ->expectsOutput('✅ Successfully transformed '.CommandTestUserData::class)
        ->assertExitCode(0);
});

it('shows error for non-existent class', function () {
    $this->artisan('dart:transform', ['class' => 'App\\NonExistentClass'])
        ->expectsOutput('Class App\\NonExistentClass does not exist')
        ->assertExitCode(1);
});

it('can run discovery mode', function () {
    $this->mock(DartTransformer::class, function ($mock) {
        $mock->shouldReceive('discoverAndTransform')
            ->once()
            ->andReturn([
                'tests/dart/user_data.dart',
                'tests/dart/status_enum.dart',
            ]);
    });

    $this->artisan('dart:transform', ['--discover' => true])
        ->expectsOutput('Discovering and transforming classes...')
        ->expectsOutput('✅ Successfully transformed 2 classes:')
        ->assertExitCode(0);
});

it('shows warning when no classes found in discovery', function () {
    $this->mock(DartTransformer::class, function ($mock) {
        $mock->shouldReceive('discoverAndTransform')
            ->once()
            ->andReturn([]);
    });

    $this->artisan('dart:transform', ['--discover' => true])
        ->expectsOutput('No applicable classes found for transformation')
        ->assertExitCode(0);
});

it('handles transformation exceptions gracefully', function () {
    $this->mock(DartTransformer::class, function ($mock) {
        $mock->shouldReceive('transformToFile')
            ->once()
            ->andThrow(new Exception('Transformation failed'));
    });

    $this->artisan('dart:transform', ['class' => CommandTestUserData::class])
        ->expectsOutput('Failed to transform '.CommandTestUserData::class.': Transformation failed')
        ->assertExitCode(1);
});
