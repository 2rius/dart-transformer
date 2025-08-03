<?php

use M2rius\DartTransformer\DartTransformer;
use M2rius\DartTransformer\Facades\DartTransformer as DartTransformerFacade;
use Spatie\LaravelData\Data;

// Test Data class for facade testing
class FacadeTestUserData extends Data
{
    public function __construct(
        public int $id,
        public string $name
    ) {}
}

it('can access dart transformer through facade', function () {
    // Mock the actual service
    $this->mock(DartTransformer::class, function ($mock) {
        $mock->shouldReceive('transform')
            ->once()
            ->with(FacadeTestUserData::class)
            ->andReturn('class FacadeTestUserData { final int id; final String name; }');
    });

    $result = DartTransformerFacade::transform(FacadeTestUserData::class);

    expect($result)->toBeString();
    expect($result)->toContain('class FacadeTestUserData');
});

it('can transform to file through facade', function () {
    $this->mock(DartTransformer::class, function ($mock) {
        $mock->shouldReceive('transformToFile')
            ->once()
            ->with(FacadeTestUserData::class)
            ->andReturn('tests/dart/facade_test_user_data.dart');
    });

    $result = DartTransformerFacade::transformToFile(FacadeTestUserData::class);

    expect($result)->toBe('tests/dart/facade_test_user_data.dart');
});

it('can discover and transform through facade', function () {
    $this->mock(DartTransformer::class, function ($mock) {
        $mock->shouldReceive('discoverAndTransform')
            ->once()
            ->withNoArgs()
            ->andReturn(['file1.dart', 'file2.dart']);
    });

    $result = DartTransformerFacade::discoverAndTransform();

    expect($result)->toBe(['file1.dart', 'file2.dart']);
});
