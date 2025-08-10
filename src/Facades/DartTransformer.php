<?php

namespace M2rius\DartTransformer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \M2rius\DartTransformer\DartTransformer
 */
class DartTransformer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \M2rius\DartTransformer\DartTransformer::class;
    }

    /**
     * Generate the aggregated Dart file using the bound service.
     */
    public static function generate(?array $classes = null): array
    {
        /** @var \M2rius\DartTransformer\DartTransformer $service */
        $service = static::resolveFacadeInstance(static::getFacadeAccessor());

        return $service->generate($classes);
    }
}
