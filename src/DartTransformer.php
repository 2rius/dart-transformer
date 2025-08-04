<?php

namespace M2rius\DartTransformer;

use M2rius\DartTransformer\Transformers\DataClassTransformer;
use M2rius\DartTransformer\Transformers\EnumTransformer;
use ReflectionClass;

class DartTransformer
{
    protected array $transformers = [];

    public array $config = [];

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
        $outputMode = $this->config['output']['mode'] ?? 'single';

        if ($outputMode === 'single') {
            // For single file mode, return the consolidated file
            return [$this->discoverAndTransformToFile($paths)];
        }

        // Behavior for separate files
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

    public function transformAllToFile(array $classes, ?string $outputPath = null): string
    {
        $allClasses = $classes;

        // Resolve missing symbols if enabled
        if ($this->config['dart']['resolve_missing_symbols'] ?? true) {
            $allClasses = $this->resolveMissingSymbols($classes);
        }

        $transformedContent = $this->buildConsolidatedContent($allClasses);

        if (! $outputPath) {
            $outputPath = $this->getConsolidatedOutputPath();
        }

        $directory = dirname($outputPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($outputPath, $transformedContent);

        return $outputPath;
    }

    public function discoverAndTransformToFile(?array $paths = null): string
    {
        $paths = $paths ?? $this->getDiscoveryPaths();
        $discoveredClasses = [];

        foreach ($paths as $path) {
            $classes = $this->discoverClasses($path);
            $discoveredClasses = array_merge($discoveredClasses, $classes);
        }

        return $this->transformAllToFile($discoveredClasses);
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
        $discoveredClasses = [];

        if (! is_dir($path)) {
            return $discoveredClasses;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $classes = $this->extractClassesFromFile($file->getPathname());
            $discoveredClasses = array_merge($discoveredClasses, $classes);
        }

        return $discoveredClasses;
    }

    protected function extractClassesFromFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $classes = [];

        // Basic regex to find classes and enums
        // This could be improved with a proper PHP parser
        preg_match_all('/^\s*(?:class|enum)\s+(\w+)/m', $content, $matches);

        if (! empty($matches[1])) {
            // Extract namespace
            preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch);
            $namespace = $namespaceMatch[1] ?? '';

            foreach ($matches[1] as $className) {
                $fullClassName = $namespace ? $namespace.'\\'.$className : $className;

                // Only include classes that can be transformed
                if ($this->canTransformClass($fullClassName)) {
                    $classes[] = $fullClassName;
                }
            }
        }

        return $classes;
    }

    protected function canTransformClass(string $className): bool
    {
        if (! class_exists($className) && ! enum_exists($className)) {
            return false;
        }

        try {
            $reflection = new ReflectionClass($className);
            foreach ($this->transformers as $transformer) {
                if ($transformer->canTransform($reflection)) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    protected function resolveMissingSymbols(array $classes): array
    {
        $allClasses = $classes;
        $processedClasses = [];

        // Keep processing until no new dependencies are found
        while (! empty($allClasses)) {
            $currentClass = array_shift($allClasses);

            if (in_array($currentClass, $processedClasses)) {
                continue;
            }

            $processedClasses[] = $currentClass;

            // Find dependencies for this class
            $dependencies = $this->findClassDependencies($currentClass);

            foreach ($dependencies as $dependency) {
                if (! in_array($dependency, $processedClasses) && ! in_array($dependency, $allClasses)) {
                    $allClasses[] = $dependency;
                }
            }
        }

        return $processedClasses;
    }

    protected function findClassDependencies(string $className): array
    {
        $dependencies = [];

        try {
            $reflection = new ReflectionClass($className);

            // Check constructor parameters for type hints
            $constructor = $reflection->getConstructor();
            if ($constructor) {
                foreach ($constructor->getParameters() as $parameter) {
                    $type = $parameter->getType();
                    if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
                        $typeName = $type->getName();
                        if ($this->canTransformClass($typeName)) {
                            $dependencies[] = $typeName;
                        }
                    }
                }
            }

            // Check properties for type hints
            foreach ($reflection->getProperties() as $property) {
                $type = $property->getType();
                if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
                    $typeName = $type->getName();
                    if ($this->canTransformClass($typeName)) {
                        $dependencies[] = $typeName;
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore errors in dependency resolution
        }

        return array_unique($dependencies);
    }

    protected function buildConsolidatedContent(array $classes): string
    {
        $imports = [];
        $classContent = [];
        $enumContent = [];

        foreach ($classes as $className) {
            try {
                $dartCode = $this->transform($className);

                // Extract imports
                if (preg_match_all("/^import\s+['\"][^'\"]+['\"];$/m", $dartCode, $importMatches)) {
                    $imports = array_merge($imports, $importMatches[0]);
                }

                // Remove imports from the code to avoid duplication
                $dartCode = preg_replace("/^import\s+['\"][^'\"]+['\"];[\r\n]*/m", '', $dartCode);

                // Organize by type
                if (enum_exists($className)) {
                    $enumContent[] = $dartCode;
                } else {
                    $classContent[] = $dartCode;
                }
            } catch (\Exception $e) {
                // Skip classes that can't be transformed
                continue;
            }
        }

        // Build final content
        $content = [];

        // Add unique imports
        $uniqueImports = array_unique($imports);
        if (! empty($uniqueImports)) {
            $content[] = implode("\n", $uniqueImports);
            $content[] = '';
        }

        // Add organized content
        if ($this->config['dart']['use_namespaces'] ?? true) {
            if (! empty($enumContent)) {
                $content[] = '// Enums';
                $content[] = implode("\n\n", $enumContent);
                $content[] = '';
            }

            if (! empty($classContent)) {
                $content[] = '// Classes';
                $content[] = implode("\n\n", $classContent);
            }
        } else {
            $content[] = implode("\n\n", array_merge($enumContent, $classContent));
        }

        return implode("\n", $content);
    }

    protected function getConsolidatedOutputPath(): string
    {
        $basePath = $this->config['output']['path'] ?? 'resources/dart';
        $filename = $this->config['output']['filename'] ?? 'generated.dart';

        return $basePath.'/'.$filename;
    }
}
