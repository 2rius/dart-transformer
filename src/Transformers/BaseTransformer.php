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

        $phpType = ltrim($phpType, '\\');

        return match ($phpType) {
            'int' => 'int',
            'float', 'double' => 'double',
            'string' => 'String',
            'bool' => 'bool',
            'array' => 'List<dynamic>',
            'object' => 'Map<String, dynamic>',
            'mixed' => 'dynamic',
            // For custom classes, resolve to a Dart-friendly class name per configured strategy
            default => $this->resolveDartClassNameFromFqcn($phpType),
        };
    }

    protected function resolveDartClassNameFromFqcn(string $fqcn): string
    {
        $strategyClass = (string) ($this->config['dart']['naming_strategy'] ?? '');

        if (! $strategyClass || ! class_exists($strategyClass)) {
            $strategyClass = \M2rius\DartTransformer\Naming\ShortClassNamingStrategy::class;
        }

        $strategy = new $strategyClass;
        if (! $strategy instanceof \M2rius\DartTransformer\Naming\NamingStrategy) {
            $strategy = new \M2rius\DartTransformer\Naming\ShortClassNamingStrategy;
        }

        return $strategy->transform($fqcn);
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
        // Ensure class declarations use the same naming strategy as type references
        return $this->resolveDartClassNameFromFqcn($reflection->getName());
    }
}
