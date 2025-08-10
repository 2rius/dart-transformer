<?php

use M2rius\DartTransformer\DartTransformer;

it('runs dart transform and generates a single aggregated file', function () {
    $this->mock(DartTransformer::class, function ($mock) {
        $mock->shouldReceive('generate')
            ->once()
            ->andReturn(['path' => 'resources/dart/generated.dart', 'count' => 3]);
    });

    $this->artisan('dart:transform')
        ->expectsOutput('Generating Dart definitions...')
        ->expectsOutput('✅ Successfully generated 3 definitions: resources/dart/generated.dart')
        ->assertExitCode(0);
});
