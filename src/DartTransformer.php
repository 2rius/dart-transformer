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

    public function generate(?array $classes = null): array
    {
        $classes = $classes ?? $this->discoverAllClasses();

        $definitions = [];

        foreach ($classes as $className) {
            try {
                $definitions[] = $this->transform($className);
            } catch (\Throwable $e) {
                continue;
            }
        }

        $outputPath = $this->getAggregatedOutputPath();
        $this->writeAggregatedFile($outputPath, $definitions);

        return ['path' => $outputPath, 'count' => count($definitions)];
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

    protected function getAggregatedOutputPath(): string
    {
        $basePath = $this->config['output']['path'] ?? 'resources/dart';
        $file = $this->config['output']['file'] ?? 'generated.dart';

        return rtrim($basePath, '/').'/'.$file;
    }

    protected function writeAggregatedFile(string $path, array $definitions): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $header = $this->buildFileHeader();
        $content = $header.implode("\n\n", $definitions)."\n";

        file_put_contents($path, $content);
    }

    protected function buildFileHeader(): string
    {
        $lines = [
            '// GENERATED CODE - DO NOT MODIFY BY HAND',
            '// ignore_for_file: type=lint',
            '',
        ];

        if ($this->config['dart']['use_json_annotation'] ?? true) {
            $lines[] = "import 'package:json_annotation/json_annotation.dart';";
            $file = $this->config['output']['file'] ?? 'generated.dart';
            $g = preg_replace('/\.dart$/', '.g.dart', $file);
            $lines[] = "part '$g';";
            $lines[] = '';
        }

        return implode("\n", $lines);
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

    protected function discoverAllClasses(): array
    {
        $paths = $this->getDiscoveryPaths();
        $classes = [];

        foreach ($paths as $path) {
            $classes = array_merge($classes, $this->discoverClasses($path));
        }

        return array_values(array_unique($classes));
    }

    protected function discoverClasses(string $path): array
    {
        $classes = [];

        $basePath = function_exists('base_path') ? base_path($path) : getcwd().DIRECTORY_SEPARATOR.$path;
        if (! is_dir($basePath)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = @file_get_contents($file->getPathname());
            if ($contents === false) {
                continue;
            }

            if (! preg_match('/^\s*namespace\s+([^;]+);/m', $contents, $nsMatch)) {
                continue;
            }

            if (preg_match('/^\s*(?:final\s+)?class\s+(\w+)/m', $contents, $classMatch)) {
                $classes[] = trim($nsMatch[1]).'\\\\'.trim($classMatch[1]);

                continue;
            }

            if (preg_match('/^\s*enum\s+(\w+)/m', $contents, $enumMatch)) {
                $classes[] = trim($nsMatch[1]).'\\\\'.trim($enumMatch[1]);

                continue;
            }
        }

        $valid = [];
        foreach ($classes as $fqcn) {
            try {
                new ReflectionClass($fqcn);
                $valid[] = $fqcn;
            } catch (\Throwable $e) {
                // skip non-loadable classes
            }
        }

        return $valid;
    }
}
