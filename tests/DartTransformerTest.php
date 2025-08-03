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

it('can transform data class to dart', function () {
    $config = [
        'dart' => [
            'use_nullable_types' => true,
            'use_json_annotation' => true,
        ],
        'transformers' => [
            'data_classes' => DataClassTransformer::class,
        ],
    ];

    $transformer = new DartTransformer($config);
    $dartCode = $transformer->transform(TestUserData::class);

    expect($dartCode)->toContain('class TestUserData');
    expect($dartCode)->toContain('final int id;');
    expect($dartCode)->toContain('final String name;');
    expect($dartCode)->toContain('final String? email;');
    expect($dartCode)->toContain('final bool isActive;');
    expect($dartCode)->toContain('@JsonSerializable()');
    expect($dartCode)->toContain('fromJson');
    expect($dartCode)->toContain('toJson');
});

it('can transform enum to dart', function () {
    $config = [
        'dart' => [
            'use_nullable_types' => true,
            'use_json_annotation' => true,
        ],
        'transformers' => [
            'enums' => EnumTransformer::class,
        ],
    ];

    $transformer = new DartTransformer($config);
    $dartCode = $transformer->transform(TestStatus::class);

    expect($dartCode)->toContain('enum TestStatus');
    expect($dartCode)->toContain('ACTIVE');
    expect($dartCode)->toContain('INACTIVE');
    expect($dartCode)->toContain('PENDING');
    expect($dartCode)->toContain("@JsonValue('active')");
});

it('generates correct file names', function () {
    $config = [
        'output' => [
            'path' => 'tests/dart',
            'extension' => '.dart',
        ],
    ];

    $transformer = new DartTransformer($config);
    $reflection = new ReflectionMethod($transformer, 'classNameToFileName');
    $reflection->setAccessible(true);

    expect($reflection->invoke($transformer, 'TestUserData'))->toBe('test_user_data');
    expect($reflection->invoke($transformer, 'UserProfile'))->toBe('user_profile');
    expect($reflection->invoke($transformer, 'API'))->toBe('a_p_i');
});

it('can determine if class can be transformed', function () {
    $config = [];
    $dataTransformer = new DataClassTransformer($config);
    $enumTransformer = new EnumTransformer($config);

    $dataReflection = new ReflectionClass(TestUserData::class);
    $enumReflection = new ReflectionClass(TestStatus::class);

    expect($dataTransformer->canTransform($dataReflection))->toBeTrue();
    expect($enumTransformer->canTransform($enumReflection))->toBeTrue();
    expect($dataTransformer->canTransform($enumReflection))->toBeFalse();
    expect($enumTransformer->canTransform($dataReflection))->toBeFalse();
});
