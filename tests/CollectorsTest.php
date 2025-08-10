<?php

use M2rius\DartTransformer\Collectors\DefaultCollector;
use M2rius\DartTransformer\Collectors\EnumCollector;
use Spatie\LaravelData\Data;

class CollectorUserData extends Data {}

enum CollectorStatus: string
{
    case A = 'a';
}

it('default collector collects data classes and @dart', function () {
    $collector = new DefaultCollector;

    $rData = new ReflectionClass(CollectorUserData::class);
    expect($collector->shouldCollect($rData))->toBeTrue();

    #[\M2rius\DartTransformer\Attributes\Dart]
    class AttrMarked {}
    $rAttr = new ReflectionClass(AttrMarked::class);
    expect($collector->shouldCollect($rAttr))->toBeTrue();

    /** @dart */
    class DocMarked {}
    $rDoc = new ReflectionClass(DocMarked::class);
    expect($collector->shouldCollect($rDoc))->toBeTrue();

    class Plain {}
    $rPlain = new ReflectionClass(Plain::class);
    expect($collector->shouldCollect($rPlain))->toBeFalse();
});

it('enum collector collects native enums and enum-like classes', function () {
    $collector = new EnumCollector;

    $rEnum = new ReflectionClass(CollectorStatus::class);
    expect($collector->shouldCollect($rEnum))->toBeTrue();

    $enumLike = new class
    {
        public const DRAFT = 'draft';
    };

    $rEnumLike = new ReflectionClass($enumLike);
    expect($collector->shouldCollect($rEnumLike))->toBeTrue();
});
