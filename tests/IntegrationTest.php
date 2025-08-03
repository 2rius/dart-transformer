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
        'output' => [
            'path' => 'tests/temp',
            'extension' => '.dart',
        ],
        'dart' => [
            'use_nullable_types' => true,
            'use_json_annotation' => false,
        ],
    ];

    $transformer = new DartTransformer($config);

    // Clean up any existing test file
    $expectedPath = 'tests/temp/integration_test_user_data.dart';
    if (file_exists($expectedPath)) {
        unlink($expectedPath);
    }

    $filePath = $transformer->transformToFile(IntegrationTestUserData::class);

    expect($filePath)->toBe($expectedPath);
    expect(file_exists($filePath))->toBeTrue();

    $content = file_get_contents($filePath);
    expect($content)->toContain('class IntegrationTestUserData');
    expect($content)->toContain('final int id');
    expect($content)->toContain('final String? email');
    expect($content)->toContain('final List<dynamic> tags');

    // Clean up
    unlink($filePath);
    if (is_dir('tests/temp')) {
        rmdir('tests/temp');
    }
});

it('can transform with custom output path', function () {
    $transformer = new DartTransformer;

    $customPath = 'tests/custom/my_user_data.dart';

    // Clean up any existing test file
    if (file_exists($customPath)) {
        unlink($customPath);
    }

    $filePath = $transformer->transformToFile(IntegrationTestUserData::class, $customPath);

    expect($filePath)->toBe($customPath);
    expect(file_exists($filePath))->toBeTrue();

    // Clean up
    unlink($filePath);
    if (is_dir('tests/custom')) {
        rmdir('tests/custom');
    }
});

it('throws exception for unsupported class', function () {
    $transformer = new DartTransformer;

    expect(fn () => $transformer->transform(stdClass::class))
        ->toThrow(InvalidArgumentException::class, 'No transformer found for class: stdClass');
});

it('can register custom transformers', function () {
    $config = [
        'transformers' => [
            'data_classes' => \M2rius\DartTransformer\Transformers\DataClassTransformer::class,
            'enums' => \M2rius\DartTransformer\Transformers\EnumTransformer::class,
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
        'output' => [
            'path' => 'tests/nested/deep/path',
            'extension' => '.dart',
        ],
    ];

    $transformer = new DartTransformer($config);

    $filePath = $transformer->transformToFile(IntegrationTestUserData::class);

    expect(file_exists($filePath))->toBeTrue();
    expect(dirname($filePath))->toBe('tests/nested/deep/path');

    // Clean up
    unlink($filePath);
    rmdir('tests/nested/deep/path');
    rmdir('tests/nested/deep');
    rmdir('tests/nested');
});

it('converts class names to snake_case correctly', function () {
    $transformer = new DartTransformer;

    // Access protected method via reflection
    $reflection = new ReflectionClass($transformer);
    $method = $reflection->getMethod('classNameToFileName');
    $method->setAccessible(true);

    expect($method->invoke($transformer, 'UserProfileData'))->toBe('user_profile_data');
    expect($method->invoke($transformer, 'APIResponse'))->toBe('a_p_i_response');
    expect($method->invoke($transformer, 'XMLHttpRequest'))->toBe('x_m_l_http_request');
    expect($method->invoke($transformer, 'SimpleClass'))->toBe('simple_class');
});

it('can handle discovery paths configuration', function () {
    $config = [
        'auto_discover' => [
            'data' => [
                'enabled' => true,
                'paths' => ['app/Data', 'app/Models'],
            ],
            'enums' => [
                'enabled' => false,
                'paths' => ['app/Enums'],
            ],
        ],
    ];

    $transformer = new DartTransformer($config);

    // Access protected method via reflection
    $reflection = new ReflectionClass($transformer);
    $method = $reflection->getMethod('getDiscoveryPaths');
    $method->setAccessible(true);

    $paths = $method->invoke($transformer);

    expect($paths)->toBe(['app/Data', 'app/Models']);
});
