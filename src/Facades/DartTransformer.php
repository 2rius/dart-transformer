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
}
