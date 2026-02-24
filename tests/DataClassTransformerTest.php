<?php

use M2rius\DartTransformer\Transformers\DataClassTransformer;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

// --- Test fixtures: Optional union types ---

class OptionalStringData extends Data
{
    public function __construct(
        public string|Optional $name,
    ) {}
}

class OptionalNullableStringData extends Data
{
    public function __construct(
        public string|null|Optional $bio,
    ) {}
}

enum OptionalTestStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

class OptionalEnumData extends Data
{
    public function __construct(
        public OptionalTestStatus|Optional $status,
    ) {}
}

class OptionalArrayData extends Data
{
    public function __construct(
        public array|Optional $tags,
    ) {}
}

// --- Test fixtures: PHPDoc @var annotations ---

class PhpDocLineData extends Data
{
    public function __construct(
        public string $name,
    ) {}
}

class PhpDocArrayBracketData extends Data
{
    public function __construct(
        /** @var PhpDocLineData[] */
        public array $lines,
    ) {}
}

class PhpDocStringArrayData extends Data
{
    public function __construct(
        /** @var string[] */
        public array $notes,
    ) {}
}

class PhpDocIntArrayData extends Data
{
    public function __construct(
        /** @var int[] */
        public array $scores,
    ) {}
}

class PhpDocGenericSyntaxData extends Data
{
    public function __construct(
        /** @var array<PhpDocLineData> */
        public array $items,
    ) {}
}

class PhpDocGenericStringData extends Data
{
    public function __construct(
        /** @var array<string> */
        public array $labels,
    ) {}
}

class PhpDocPlainArrayData extends Data
{
    public function __construct(
        public array $misc,
    ) {}
}

// --- Test fixtures: Combined Optional + PHPDoc ---

class CombinedOptionalPhpDocData extends Data
{
    public function __construct(
        /** @var PhpDocLineData[] */
        public array|Optional $lines,
    ) {}
}

class CombinedOptionalNullablePhpDocData extends Data
{
    public function __construct(
        /** @var string[] */
        public array|null|Optional $tags,
    ) {}
}

// --- Tests: Optional union types ---

it('transforms string|Optional to String?', function () {
    $t = new DataClassTransformer(['dart' => ['use_json_annotation' => false]]);
    $code = $t->transform(new ReflectionClass(OptionalStringData::class));

    expect($code)->toContain('final String? name;');
});

it('transforms string|null|Optional to String?', function () {
    $t = new DataClassTransformer(['dart' => ['use_json_annotation' => false]]);
    $code = $t->transform(new ReflectionClass(OptionalNullableStringData::class));

    expect($code)->toContain('final String? bio;');
});

it('transforms Enum|Optional to Enum?', function () {
    $t = new DataClassTransformer(['dart' => ['use_json_annotation' => false]]);
    $code = $t->transform(new ReflectionClass(OptionalEnumData::class));

    expect($code)->toContain('final OptionalTestStatus? status;');
});

it('transforms array|Optional to List<dynamic>?', function () {
    $t = new DataClassTransformer(['dart' => ['use_json_annotation' => false]]);
    $code = $t->transform(new ReflectionClass(OptionalArrayData::class));

    expect($code)->toContain('final List<dynamic>? tags;');
});

// --- Tests: PHPDoc @var annotations ---

it('transforms array with @var SomeClass[] to List<SomeClass>', function () {
    $t = new DataClassTransformer(['dart' => ['use_json_annotation' => false]]);
    $code = $t->transform(new ReflectionClass(PhpDocArrayBracketData::class));

    expect($code)->toContain('final List<PhpDocLineData> lines;');
});

it('transforms array with @var string[] to List<String>', function () {
    $t = new DataClassTransformer(['dart' => ['use_json_annotation' => false]]);
    $code = $t->transform(new ReflectionClass(PhpDocStringArrayData::class));

    expect($code)->toContain('final List<String> notes;');
});

it('transforms array with @var int[] to List<int>', function () {
    $t = new DataClassTransformer(['dart' => ['use_json_annotation' => false]]);
    $code = $t->transform(new ReflectionClass(PhpDocIntArrayData::class));

    expect($code)->toContain('final List<int> scores;');
});

it('transforms array with @var array<SomeClass> to List<SomeClass>', function () {
    $t = new DataClassTransformer(['dart' => ['use_json_annotation' => false]]);
    $code = $t->transform(new ReflectionClass(PhpDocGenericSyntaxData::class));

    expect($code)->toContain('final List<PhpDocLineData> items;');
});

it('transforms array with @var array<string> to List<String>', function () {
    $t = new DataClassTransformer(['dart' => ['use_json_annotation' => false]]);
    $code = $t->transform(new ReflectionClass(PhpDocGenericStringData::class));

    expect($code)->toContain('final List<String> labels;');
});

it('transforms plain array without @var to List<dynamic>', function () {
    $t = new DataClassTransformer(['dart' => ['use_json_annotation' => false]]);
    $code = $t->transform(new ReflectionClass(PhpDocPlainArrayData::class));

    expect($code)->toContain('final List<dynamic> misc;');
});

// --- Tests: Combined Optional + PHPDoc ---

it('transforms array|Optional with @var SomeClass[] to List<SomeClass>?', function () {
    $t = new DataClassTransformer(['dart' => ['use_json_annotation' => false]]);
    $code = $t->transform(new ReflectionClass(CombinedOptionalPhpDocData::class));

    expect($code)->toContain('final List<PhpDocLineData>? lines;');
});

it('transforms array|null|Optional with @var string[] to List<String>?', function () {
    $t = new DataClassTransformer(['dart' => ['use_json_annotation' => false]]);
    $code = $t->transform(new ReflectionClass(CombinedOptionalNullablePhpDocData::class));

    expect($code)->toContain('final List<String>? tags;');
});
