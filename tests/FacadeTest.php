<?php

use M2rius\DartTransformer\DartTransformer;
use M2rius\DartTransformer\Facades\DartTransformer as DartTransformerFacade;

it('can trigger generation through facade', function () {
    $this->mock(DartTransformer::class, function ($mock) {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn(['path' => 'resources/dart/generated.dart', 'count' => 5]);
    });

    $result = DartTransformerFacade::generate();

    expect($result)->toBe(['path' => 'resources/dart/generated.dart', 'count' => 5]);
});
