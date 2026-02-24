<?php

namespace M2rius\DartTransformer\Transformers;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

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
            $dartType = $this->resolvePropertyDartType($type->getName(), $property);

            return $type->allowsNull() ? $this->makeNullable($dartType) : $dartType;
        }

        if ($type instanceof ReflectionUnionType) {
            $hasNull = false;
            $hasOptional = false;
            $dartTypes = [];

            foreach ($type->getTypes() as $t) {
                $name = $t->getName();

                if ($name === 'null') {
                    $hasNull = true;
                } elseif ($name === Optional::class) {
                    $hasOptional = true;
                } else {
                    $dartTypes[] = $this->resolvePropertyDartType($name, $property);
                }
            }

            if (count($dartTypes) === 1) {
                $dartType = $dartTypes[0];

                return ($hasNull || $hasOptional) ? $this->makeNullable($dartType) : $dartType;
            }

            return 'dynamic';
        }

        return 'dynamic';
    }

    protected function resolvePropertyDartType(string $phpType, ReflectionProperty $property): string
    {
        if ($phpType === 'array') {
            $elementType = $this->parseVarDocElementType($property);

            if ($elementType !== null) {
                $dartElementType = $this->phpTypeToDartType($elementType);

                return "List<{$dartElementType}>";
            }
        }

        return $this->phpTypeToDartType($phpType);
    }

    protected function parseVarDocElementType(ReflectionProperty $property): ?string
    {
        $doc = $property->getDocComment();

        if (! $doc) {
            return null;
        }

        if (preg_match('/@var\s+([\w\\\\]+)\[\]/', $doc, $matches)) {
            return $matches[1];
        }

        if (preg_match('/@var\s+array<([\w\\\\]+)>/', $doc, $matches)) {
            return $matches[1];
        }

        return null;
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
