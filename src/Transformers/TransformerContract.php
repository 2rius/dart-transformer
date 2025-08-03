<?php

namespace M2rius\DartTransformer\Transformers;

use ReflectionClass;

interface TransformerContract
{
    public function canTransform(ReflectionClass $reflection): bool;

    public function transform(ReflectionClass $reflection): string;
}
