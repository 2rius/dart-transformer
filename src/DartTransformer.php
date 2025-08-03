<?php

namespace M2rius\DartTransformer;

use M2rius\DartTransformer\Transformers\DataClassTransformer;
use M2rius\DartTransformer\Transformers\EnumTransformer;
use ReflectionClass;

class DartTransformer
{
    protected array $transformers = [];

    protected array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->registerDefaultTransformers();
    }

    public function transform(string $className): string
    {
        $reflection = new ReflectionClass($className);

        foreach ($this->transformers as $transformer) {
            if ($transformer->canTransform($reflection)) {
                return $transformer->transform($reflection);
            }
        }

        throw new \InvalidArgumentException("No transformer found for class: {$className}");
    }

    public function transformToFile(string $className, ?string $outputPath = null): string
    {
        $dartCode = $this->transform($className);

        if (! $outputPath) {
            $outputPath = $this->getDefaultOutputPath($className);
        }

        $directory = dirname($outputPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($outputPath, $dartCode);

        return $outputPath;
    }

    public function discoverAndTransform(?array $paths = null): array
    {
        $paths = $paths ?? $this->getDiscoveryPaths();
        $transformedFiles = [];

        foreach ($paths as $path) {
            $classes = $this->discoverClasses($path);

            foreach ($classes as $className) {
                try {
                    $outputPath = $this->transformToFile($className);
                    $transformedFiles[] = $outputPath;
                } catch (\Exception $e) {
                    // Log error or handle silently
                    continue;
                }
            }
        }

        return $transformedFiles;
    }

    protected function registerDefaultTransformers(): void
    {
        $transformerClasses = $this->config['transformers'] ?? [
            'data_classes' => DataClassTransformer::class,
            'enums' => EnumTransformer::class,
        ];

        foreach ($transformerClasses as $transformerClass) {
            $this->transformers[] = new $transformerClass($this->config);
        }
    }

    protected function getDefaultOutputPath(string $className): string
    {
        $basePath = $this->config['output']['path'] ?? 'resources/dart';
        $extension = $this->config['output']['extension'] ?? '.dart';

        $fileName = $this->classNameToFileName($className);

        return $basePath.'/'.$fileName.$extension;
    }

    protected function classNameToFileName(string $className): string
    {
        $parts = explode('\\', $className);
        $className = end($parts);

        // Convert PascalCase to snake_case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }

    protected function getDiscoveryPaths(): array
    {
        $paths = [];

        if ($this->config['auto_discover']['data']['enabled'] ?? false) {
            $paths = array_merge($paths, $this->config['auto_discover']['data']['paths'] ?? []);
        }

        if ($this->config['auto_discover']['enums']['enabled'] ?? false) {
            $paths = array_merge($paths, $this->config['auto_discover']['enums']['paths'] ?? []);
        }

        return $paths;
    }

    protected function discoverClasses(string $path): array
    {
        // This is a simplified implementation
        // In a real implementation, you'd use reflection or file scanning
        // to discover classes in the given path
        return [];
    }
}
