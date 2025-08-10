<?php

namespace M2rius\DartTransformer\Collectors;

use ReflectionClass;

class EnumCollector
{
    public function __construct() {}

    public function shouldCollect(ReflectionClass $reflection): bool
    {
        try {
            if ($reflection->isEnum()) {
                return true;
            }
        } catch (\Throwable $e) {
        }

        // Fallback: classes with only public string constants and no constructor
        $constants = $reflection->getConstants();
        if (! empty($constants) && $reflection->getParentClass() === false && ! $reflection->hasMethod('__construct')) {
            return true;
        }

        return false;
    }
}
