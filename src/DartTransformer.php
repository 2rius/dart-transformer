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
        $classes = $classes ?? $this->collectTransformableClasses();

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
            DataClassTransformer::class,
            EnumTransformer::class,
        ];

        foreach ($transformerClasses as $transformerClass) {
            $this->transformers[] = new $transformerClass($this->config);
        }
    }

    protected function getAggregatedOutputPath(): string
    {
        $outputFile = $this->config['output_file']
            ?? (function_exists('resource_path') ? resource_path('dart/generated.dart') : 'resources/dart/generated.dart');

        return $outputFile;
    }

    protected function writeAggregatedFile(string $path, array $definitions): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $header = $this->buildFileHeader();
        $content = $header.implode("\n", $definitions)."\n";

        file_put_contents($path, $content);

        $this->applyFormatter($path);
    }

    protected function buildFileHeader(): string
    {
        $lines = [];

        $customHeader = $this->config['dart']['header'] ?? null;
        if (is_string($customHeader) && trim($customHeader) !== '') {
            $lines[] = rtrim($customHeader);
            $lines[] = '';
        }

        $lines[] = '// GENERATED CODE - DO NOT MODIFY BY HAND';
        $lines[] = '// ignore_for_file: type=lint';
        $lines[] = '';

        if ($this->config['dart']['use_json_annotation'] ?? true) {
            $lines[] = "import 'package:json_annotation/json_annotation.dart';";
            $file = basename($this->getAggregatedOutputPath());
            $g = preg_replace('/\.dart$/', '.g.dart', $file);
            $lines[] = "part '$g';";
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    protected function applyFormatter(string $filePath): void
    {
        $formatterClass = $this->config['formatter'] ?? null;
        if (! is_string($formatterClass) || $formatterClass === '') {
            return;
        }

        if (! class_exists($formatterClass)) {
            return;
        }

        try {
            $formatter = new $formatterClass;
            if (method_exists($formatter, 'format')) {
                $formatter->format($filePath);
            }
        } catch (\Throwable $e) {
            // Silently ignore formatting errors
        }
    }

    protected function getDiscoveryPaths(): array
    {
        $paths = $this->config['auto_discover_types'] ?? [];

        return array_values(array_filter($paths));
    }

    protected function collectTransformableClasses(): array
    {
        $paths = $this->getDiscoveryPaths();
        $allFoundClasses = [];
        foreach ($paths as $path) {
            $allFoundClasses = array_merge($allFoundClasses, $this->discoverClasses($path));
        }

        $allFoundClasses = array_values(array_unique($allFoundClasses));

        $collectors = $this->config['collectors'] ?? [];
        if (empty($collectors)) {
            return $allFoundClasses; // fallback: everything discovered
        }

        $selected = [];
        foreach ($collectors as $collectorClass) {
            if (! class_exists($collectorClass)) {
                continue;
            }
            $collector = new $collectorClass($this->config);
            if (! method_exists($collector, 'shouldCollect')) {
                continue;
            }
            foreach ($allFoundClasses as $fqcn) {
                try {
                    $reflection = new ReflectionClass($fqcn);
                } catch (\Throwable $e) {
                    continue;
                }
                if ($collector->shouldCollect($reflection)) {
                    $selected[] = $fqcn;
                }
            }
        }

        return array_values(array_unique($selected));
    }

    protected function discoverClasses(string $path): array
    {
        $classes = [];

        $basePath = is_callable($path) ? (string) $path() : $path;
        // Prefer given path if it exists; otherwise try resolving via base_path
        if (! is_dir($basePath) && function_exists('base_path')) {
            $resolved = base_path($basePath);
            if (is_dir($resolved)) {
                $basePath = $resolved;
            }
        }
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
                $fqcn = trim($nsMatch[1]).'\\'.trim($classMatch[1]);
                if (! class_exists($fqcn)) {
                    @require_once $file->getPathname();
                }
                $classes[] = $fqcn;

                continue;
            }

            if (preg_match('/^\s*enum\s+(\w+)/m', $contents, $enumMatch)) {
                $fqcn = trim($nsMatch[1]).'\\'.trim($enumMatch[1]);
                if (! class_exists($fqcn)) {
                    @require_once $file->getPathname();
                }
                $classes[] = $fqcn;

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
