<?php

namespace M2rius\DartTransformer\Collectors;

use ReflectionClass;

class DefaultCollector
{
    public function __construct() {}

    public function shouldCollect(ReflectionClass $reflection): bool
    {
        // Collect Spatie Laravel Data classes by default
        try {
            if (class_exists('Spatie\\LaravelData\\Data') && $reflection->isSubclassOf('Spatie\\LaravelData\\Data')) {
                return true;
            }
        } catch (\Throwable $e) {
        }

        // Allow opting-in via attribute or docblock tag
        if ($this->hasDartAttributeOrTag($reflection)) {
            return true;
        }

        return false;
    }

    protected function hasDartAttributeOrTag(ReflectionClass $reflection): bool
    {
        // PHP 8 attributes
        foreach ($reflection->getAttributes() as $attr) {
            $name = ltrim($attr->getName(), '\\');
            if (in_array($name, [
                'M2rius\\DartTransformer\\Attributes\\Dart',
                'Dart',
            ], true)) {
                return true;
            }
        }

        // Docblock tag
        $doc = $reflection->getDocComment() ?: '';
        if (stripos($doc, '@dart') !== false) {
            return true;
        }

        return false;
    }
}
