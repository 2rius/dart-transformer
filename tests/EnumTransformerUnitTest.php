<?php

use M2rius\DartTransformer\Transformers\EnumTransformer;

// Native backed enum
enum UnitTestBackedStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

// Native unit enum
enum UnitTestUnitEnum
{
    case ONE;
    case TWO;
}

// Laravel-like enum class (constants only, no constructor)
class UnitTestLaravelLikeEnum
{
    public const DRAFT = 'draft';

    public const VERSION = 1;
}

// Not an enum and not enum-like (has constructor)
class UnitTestNotEnum
{
    public const X = 'x';

    public function __construct() {}
}

it('canTransform returns true for native enums and laravel-like enums, false otherwise', function () {
    $t = new EnumTransformer;

    expect($t->canTransform(new ReflectionClass(UnitTestBackedStatus::class)))->toBeTrue();
    expect($t->canTransform(new ReflectionClass(UnitTestUnitEnum::class)))->toBeTrue();
    expect($t->canTransform(new ReflectionClass(UnitTestLaravelLikeEnum::class)))->toBeTrue();
    expect($t->canTransform(new ReflectionClass(UnitTestNotEnum::class)))->toBeFalse();
});

it('transforms native backed enums with @JsonValue when enabled', function () {
    $t = new EnumTransformer(['dart' => ['use_json_annotation' => true]]);
    $code = $t->transform(new ReflectionClass(UnitTestBackedStatus::class));

    expect($code)->toContain('enum UnitTestBackedStatus');
    expect($code)->toContain("@JsonValue('active')\nACTIVE,");
    expect($code)->toContain("@JsonValue('inactive')\nINACTIVE,");
});

it('transforms native unit enums with @JsonValue using case name when enabled', function () {
    $t = new EnumTransformer(['dart' => ['use_json_annotation' => true]]);
    $code = $t->transform(new ReflectionClass(UnitTestUnitEnum::class));

    expect($code)->toContain("@JsonValue('ONE')\nONE,");
    expect($code)->toContain("@JsonValue('TWO')\nTWO,");
});

it('transforms native enums without @JsonValue when disabled', function () {
    $t = new EnumTransformer(['dart' => ['use_json_annotation' => false]]);
    $code = $t->transform(new ReflectionClass(UnitTestBackedStatus::class));

    expect($code)->not->toContain('@JsonValue(');
    expect($code)->toContain('ACTIVE,');
    expect($code)->toContain('INACTIVE,');
});

it('transforms laravel-like enums; annotates only string constants when enabled', function () {
    $t = new EnumTransformer(['dart' => ['use_json_annotation' => true]]);
    $code = $t->transform(new ReflectionClass(UnitTestLaravelLikeEnum::class));

    expect($code)->toContain('enum UnitTestLaravelLikeEnum');
    expect($code)->toContain("@JsonValue('draft')\nDRAFT,");
    // VERSION is int -> no annotation
    expect($code)->toContain('VERSION,');
});

it('throws for non-enum and non-laravel-like classes', function () {
    $t = new EnumTransformer;
    $r = new ReflectionClass(UnitTestNotEnum::class);

    expect(fn () => $t->transform($r))
        ->toThrow(InvalidArgumentException::class, 'Cannot transform class UnitTestNotEnum as enum');
});
