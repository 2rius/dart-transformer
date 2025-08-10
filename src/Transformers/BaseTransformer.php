<?php

namespace M2rius\DartTransformer\Transformers;

use ReflectionClass;

abstract class BaseTransformer implements TransformerContract
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    abstract public function canTransform(ReflectionClass $reflection): bool;

    abstract public function transform(ReflectionClass $reflection): string;

    protected function phpTypeToDartType(string $phpType): string
    {
        // Apply configured replacements first
        foreach (($this->config['default_type_replacements'] ?? []) as $replaced => $dartType) {
            if (strcasecmp(ltrim($replaced, '\\'), ltrim($phpType, '\\')) === 0) {
                return $dartType;
            }
        }

        return match ($phpType) {
            'int' => 'int',
            'float', 'double' => 'double',
            'string' => 'String',
            'bool' => 'bool',
            'array' => 'List<dynamic>',
            'object' => 'Map<String, dynamic>',
            'mixed' => 'dynamic',
            default => $phpType, // For custom classes, keep as is
        };
    }

    protected function makeNullable(string $dartType): string
    {
        if ($this->config['dart']['use_nullable_types'] ?? true) {
            return $dartType.'?';
        }

        return $dartType;
    }

    protected function getClassName(ReflectionClass $reflection): string
    {
        return $reflection->getShortName();
    }
}
