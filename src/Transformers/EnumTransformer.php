<?php

namespace M2rius\DartTransformer\Transformers;

use ReflectionClass;
use ReflectionClassConstant;
use ReflectionEnum;
use ReflectionEnumBackedCase;

class EnumTransformer extends BaseTransformer
{
    public function canTransform(ReflectionClass $reflection): bool
    {
        return $reflection->isEnum() || $this->isLaravelEnum($reflection);
    }

    public function transform(ReflectionClass $reflection): string
    {
        $className = $this->getClassName($reflection);

        if ($reflection->isEnum()) {
            return $this->transformNativeEnum($reflection);
        }

        if ($this->isLaravelEnum($reflection)) {
            return $this->transformLaravelEnum($reflection);
        }

        throw new \InvalidArgumentException("Cannot transform class {$className} as enum");
    }

    protected function transformNativeEnum(ReflectionClass $reflection): string
    {
        $className = $this->getClassName($reflection);

        $dartCode = [];

        // Aggregated file will contain imports

        $dartCode[] = "enum {$className} {";

        // Get enum cases correctly for PHP 8.1+
        $enumReflection = new ReflectionEnum($reflection->getName());
        $enumCases = $enumReflection->getCases();

        foreach ($enumCases as $case) {
            if ($this->config['dart']['use_json_annotation'] ?? true) {
                // Use getBackingValue() for backed enums, getName() for unit enums
                $value = $case instanceof ReflectionEnumBackedCase
                    ? $case->getBackingValue()
                    : $case->getName();
                $dartCode[] = "  @JsonValue('{$value}')";
            }
            $dartCode[] = "  {$case->getName()},";
        }

        $dartCode[] = '}';

        return implode("\n", $dartCode);
    }

    protected function transformLaravelEnum(ReflectionClass $reflection): string
    {
        $className = $this->getClassName($reflection);
        $constants = $reflection->getConstants(ReflectionClassConstant::IS_PUBLIC);

        $dartCode = [];

        // Aggregated file will contain imports

        $dartCode[] = "enum {$className} {";

        foreach ($constants as $name => $value) {
            if (is_string($value)) {
                $dartCode[] = "  @JsonValue('{$value}')";
                $dartCode[] = "  {$name},";
            } else {
                $dartCode[] = "  {$name},";
            }
        }

        $dartCode[] = '}';

        return implode("\n", $dartCode);
    }

    protected function isLaravelEnum(ReflectionClass $reflection): bool
    {
        // Check if class extends a common Laravel enum base class
        // or has constants that suggest it's an enum-like class
        $constants = $reflection->getConstants(ReflectionClassConstant::IS_PUBLIC);

        return ! empty($constants) &&
               $reflection->getParentClass() === false &&
               ! $reflection->hasMethod('__construct');
    }
}
