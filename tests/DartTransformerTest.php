<?php

use M2rius\DartTransformer\DartTransformer;
use M2rius\DartTransformer\Transformers\DataClassTransformer;
use M2rius\DartTransformer\Transformers\EnumTransformer;
use Spatie\LaravelData\Data;

// Test Data class
class TestUserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $email,
        public bool $isActive
    ) {}
}

// Test Enum
enum TestStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

it('aggregates transformed code into a single output', function () {
    $config = [
        'output_file' => 'tests/dart/generated.dart',
        'dart' => [
            'use_nullable_types' => true,
            'use_json_annotation' => true,
        ],
        'transformers' => [
            DataClassTransformer::class,
            EnumTransformer::class,
        ],
    ];

    $transformer = new DartTransformer($config);

    // Simulate discovery result
    $classes = [TestUserData::class, TestStatus::class];
    expect(method_exists($transformer, 'generate'))
        ->toBeTrue();

    $result = $transformer->generate($classes);

    expect($result['path'])->toBe('tests/dart/generated.dart');
    expect($result['count'])->toBe(2);
    expect(file_exists('tests/dart/generated.dart'))->toBeTrue();

    $content = file_get_contents('tests/dart/generated.dart');
    expect($content)->toContain('class TestUserData');
    expect($content)->toContain('enum TestStatus');

    // cleanup
    unlink('tests/dart/generated.dart');
    @rmdir('tests/dart');
});
