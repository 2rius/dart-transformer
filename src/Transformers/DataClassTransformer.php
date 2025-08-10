<?php

namespace M2rius\DartTransformer\Transformers;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Spatie\LaravelData\Data;

class DataClassTransformer extends BaseTransformer
{
    public function canTransform(ReflectionClass $reflection): bool
    {
        return $reflection->isSubclassOf(Data::class);
    }

    public function transform(ReflectionClass $reflection): string
    {
        $className = $this->getClassName($reflection);
        $properties = $this->getProperties($reflection);

        $dartCode = [];

        // Aggregated file will contain imports; avoid per-class imports/parts

        // Add JSON annotation if enabled
        if ($this->config['dart']['use_json_annotation'] ?? true) {
            $dartCode[] = '@JsonSerializable()';
        }

        $dartCode[] = "class {$className} {";

        // Add properties
        foreach ($properties as $property) {
            $dartCode[] = $property;
        }

        // Add constructor
        $dartCode[] = $this->generateConstructor($className, $properties);

        // Add JSON methods if enabled
        if ($this->config['dart']['use_json_annotation'] ?? true) {
            $dartCode[] = "factory {$className}.fromJson(Map<String, dynamic> json)=>_\${$className}FromJson(json);";
            $dartCode[] = "Map<String, dynamic> toJson()=>_\${$className}ToJson(this);";
        }

        $dartCode[] = '}';

        return implode("\n", $dartCode);
    }

    protected function getProperties(ReflectionClass $reflection): array
    {
        $properties = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $type = $this->getPropertyType($property);
            $name = $property->getName();

            $properties[] = "final {$type} {$name};";
        }

        return $properties;
    }

    protected function getPropertyType(ReflectionProperty $property): string
    {
        $type = $property->getType();

        if (! $type) {
            return 'dynamic';
        }

        if ($type instanceof ReflectionNamedType) {
            $dartType = $this->phpTypeToDartType($type->getName());

            return $type->allowsNull() ? $this->makeNullable($dartType) : $dartType;
        }

        if ($type instanceof ReflectionUnionType) {
            $types = array_map(fn ($t) => $this->phpTypeToDartType($t->getName()), $type->getTypes());

            // Handle nullable union types
            if (in_array('null', $types)) {
                $types = array_filter($types, fn ($t) => $t !== 'null');
                if (count($types) === 1) {
                    return $this->makeNullable($types[0]);
                }
            }

            return 'dynamic'; // Fallback for complex union types
        }

        return 'dynamic';
    }

    protected function generateConstructor(string $className, array $properties): string
    {
        $propertyNames = [];

        foreach ($properties as $property) {
            // Extract property name from "final Type name;" format
            if (preg_match('/final\s+\S+\s+(\w+);/', $property, $matches)) {
                $propertyNames[] = "required this.{$matches[1]}";
            }
        }

        if (empty($propertyNames)) {
            return "const {$className}();";
        }

        return "const {$className}({\n".implode(",\n", $propertyNames).",\n});";
    }

    // No longer needed for aggregated output
}
