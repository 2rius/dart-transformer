<?php

use M2rius\DartTransformer\DartTransformer;
use Spatie\LaravelData\Data;

// Test classes for integration testing
class IntegrationTestUserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $email,
        public bool $isActive,
        public array $tags = []
    ) {}
}

enum IntegrationTestStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

class IntegrationTestSimpleEnum
{
    const DRAFT = 'draft';

    const PUBLISHED = 'published';

    const ARCHIVED = 'archived';
}

it('can handle file output operations', function () {
    $config = [
        'output_file' => 'tests/temp/generated.dart',
        'dart' => [
            'use_nullable_types' => true,
            'use_json_annotation' => false,
        ],
    ];

    $transformer = new DartTransformer($config);

    // Clean up any existing test file
    $generated = 'tests/temp/generated.dart';
    if (file_exists($generated)) {
        unlink($generated);
    }

    $result = $transformer->generate([IntegrationTestUserData::class]);

    expect($result['path'])->toBe($generated);
    expect(file_exists($generated))->toBeTrue();

    $content = file_get_contents($generated);
    expect($content)->toContain('class IntegrationTestUserData');
    expect($content)->toContain('final int id');
    expect($content)->toContain('final String? email');
    expect($content)->toContain('final List<dynamic> tags');

    // Clean up
    unlink($generated);
    if (is_dir('tests/temp')) {
        rmdir('tests/temp');
    }
});

// Removed per-class transformToFile API in favor of aggregated generation

it('throws exception for unsupported class', function () {
    $transformer = new DartTransformer;

    expect(fn () => $transformer->transform(stdClass::class))
        ->toThrow(InvalidArgumentException::class, 'No transformer found for class: stdClass');
});

it('can register custom transformers', function () {
    $config = [
        'transformers' => [
            \M2rius\DartTransformer\Transformers\DataClassTransformer::class,
            \M2rius\DartTransformer\Transformers\EnumTransformer::class,
        ],
    ];

    $transformer = new DartTransformer($config);

    // Should work with registered transformers - test that it returns a string
    $result = $transformer->transform(IntegrationTestUserData::class);
    expect($result)->toBeString();
    expect($result)->toContain('class IntegrationTestUserData');
});

it('handles directory creation for nested paths', function () {
    $config = [
        'output_file' => 'tests/nested/deep/path/generated.dart',
    ];

    $transformer = new DartTransformer($config);

    $result = $transformer->generate([IntegrationTestUserData::class]);

    expect(file_exists($result['path']))->toBeTrue();
    expect(dirname($result['path']))->toBe('tests/nested/deep/path');

    // Clean up
    unlink($result['path']);
    rmdir('tests/nested/deep/path');
    rmdir('tests/nested/deep');
    rmdir('tests/nested');
});

// Removed: per-class filename logic is not used in aggregated generation

it('can handle auto discover types configuration', function () {
    $config = [
        'auto_discover_types' => ['app', 'app/Domain'],
    ];

    $transformer = new DartTransformer($config);

    // Access protected method via reflection
    $reflection = new ReflectionClass($transformer);
    $method = $reflection->getMethod('getDiscoveryPaths');
    $method->setAccessible(true);

    $paths = $method->invoke($transformer);

    expect($paths)->toBe(['app', 'app/Domain']);
});
