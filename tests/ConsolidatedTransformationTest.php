<?php

use M2rius\DartTransformer\DartTransformer;
use Spatie\LaravelData\Data;

// Test classes for consolidated transformation
class ConsolidatedTestUserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $email,
        public ConsolidatedTestStatus $status
    ) {}
}

class ConsolidatedTestProfileData extends Data
{
    public function __construct(
        public int $userId,
        public string $bio,
        public array $preferences = []
    ) {}
}

enum ConsolidatedTestStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

enum ConsolidatedTestRole: string
{
    case USER = 'user';
    case ADMIN = 'admin';
    case MODERATOR = 'moderator';
}

it('can transform multiple classes to single consolidated file', function () {
    $config = [
        'output' => [
            'path' => 'tests/temp',
            'filename' => 'generated.dart',
        ],
        'dart' => [
            'use_nullable_types' => true,
            'use_json_annotation' => true,
        ],
    ];

    $transformer = new DartTransformer($config);

    // Clean up any existing test file
    $expectedPath = 'tests/temp/generated.dart';
    if (file_exists($expectedPath)) {
        unlink($expectedPath);
    }

    // Transform multiple classes
    $classes = [
        ConsolidatedTestUserData::class,
        ConsolidatedTestProfileData::class,
        ConsolidatedTestStatus::class,
        ConsolidatedTestRole::class,
    ];

    $filePath = $transformer->transformAllToFile($classes);

    expect($filePath)->toBe($expectedPath);
    expect(file_exists($filePath))->toBeTrue();

    $content = file_get_contents($filePath);

    // All classes should be in the single file
    expect($content)->toContain('class ConsolidatedTestUserData');
    expect($content)->toContain('class ConsolidatedTestProfileData');
    expect($content)->toContain('enum ConsolidatedTestStatus');
    expect($content)->toContain('enum ConsolidatedTestRole');

    // Dependencies should be resolved (ConsolidatedTestStatus used in UserData)
    expect($content)->toContain('final ConsolidatedTestStatus status');

    // JSON annotations should be present
    expect($content)->toContain('@JsonSerializable()');
    expect($content)->toContain('fromJson');
    expect($content)->toContain('toJson');

    // Clean up
    unlink($filePath);
    if (is_dir('tests/temp')) {
        $files = glob('tests/temp/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir('tests/temp');
    }
});

it('can discover and transform all classes to single file', function () {
    $config = [
        'output' => [
            'path' => 'tests/temp',
            'filename' => 'discovered.dart',
        ],
    ];

    $transformer = new DartTransformer($config);

    // Clean up any existing test file
    $expectedPath = 'tests/temp/discovered.dart';
    if (file_exists($expectedPath)) {
        unlink($expectedPath);
    }

    // Simulate discovery by providing classes directly
    $classes = [
        ConsolidatedTestUserData::class,
        ConsolidatedTestStatus::class,
    ];

    $filePath = $transformer->transformAllToFile($classes);

    expect($filePath)->toBe($expectedPath);
    expect(file_exists($filePath))->toBeTrue();

    $content = file_get_contents($filePath);
    expect($content)->toContain('class ConsolidatedTestUserData');
    expect($content)->toContain('enum ConsolidatedTestStatus');

    // Clean up
    unlink($filePath);
    if (is_dir('tests/temp')) {
        $files = glob('tests/temp/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir('tests/temp');
    }
});

it('organizes output by namespaces', function () {
    $config = [
        'output' => [
            'path' => 'tests/temp',
            'filename' => 'namespaced.dart',
        ],
        'dart' => [
            'use_namespaces' => true,
        ],
    ];

    $transformer = new DartTransformer($config);

    // Clean up any existing test file
    $expectedPath = 'tests/temp/namespaced.dart';
    if (file_exists($expectedPath)) {
        unlink($expectedPath);
    }

    $classes = [
        ConsolidatedTestUserData::class,
        ConsolidatedTestStatus::class,
    ];

    $filePath = $transformer->transformAllToFile($classes);

    expect(file_exists($filePath))->toBeTrue();

    $content = file_get_contents($filePath);

    // Should organize by namespace-like structure
    expect($content)->toContain('// Classes');
    expect($content)->toContain('// Enums');

    // Clean up
    unlink($filePath);
    if (is_dir('tests/temp')) {
        $files = glob('tests/temp/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir('tests/temp');
    }
});

it('handles missing symbol resolution', function () {
    $config = [
        'output' => [
            'path' => 'tests/temp',
            'filename' => 'symbols.dart',
        ],
    ];

    $transformer = new DartTransformer($config);

    // Clean up any existing test file
    $expectedPath = 'tests/temp/symbols.dart';
    if (file_exists($expectedPath)) {
        unlink($expectedPath);
    }

    // Transform UserData which depends on Status, but only provide UserData
    // The transformer should discover Status as a missing symbol
    $filePath = $transformer->transformAllToFile([ConsolidatedTestUserData::class]);

    expect(file_exists($filePath))->toBeTrue();

    $content = file_get_contents($filePath);

    // Should contain both the requested class and its dependency
    expect($content)->toContain('class ConsolidatedTestUserData');
    expect($content)->toContain('enum ConsolidatedTestStatus');

    // Clean up
    unlink($filePath);
    if (is_dir('tests/temp')) {
        $files = glob('tests/temp/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir('tests/temp');
    }
});
