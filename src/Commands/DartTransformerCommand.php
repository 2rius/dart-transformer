<?php

namespace M2rius\DartTransformer\Commands;

use Illuminate\Console\Command;
use M2rius\DartTransformer\DartTransformer;

class DartTransformerCommand extends Command
{
    public $signature = 'dart:transform
                        {class? : The specific class to transform}
                        {--output= : Output directory for generated Dart files}
                        {--discover : Automatically discover and transform all applicable classes}';

    public $description = 'Transform PHP classes to Dart equivalents';

    public function handle(): int
    {
        $transformer = app(DartTransformer::class);

        $className = $this->argument('class');
        $outputPath = $this->option('output');
        $discover = $this->option('discover');

        if ($discover) {
            return $this->handleDiscovery($transformer);
        }

        if ($className) {
            return $this->handleSingleClass($transformer, $className, $outputPath);
        }

        $this->info('Please specify a class to transform or use --discover to transform all applicable classes');
        $this->line('');
        $this->line('Examples:');
        $this->line('  php artisan dart:transform App\\Data\\UserData');
        $this->line('  php artisan dart:transform --discover');
        $this->line('  php artisan dart:transform App\\Data\\UserData --output=resources/dart/models');

        return self::SUCCESS;
    }

    protected function handleSingleClass(DartTransformer $transformer, string $className, ?string $outputPath): int
    {
        try {
            if (! class_exists($className)) {
                $this->error("Class {$className} does not exist");

                return self::FAILURE;
            }

            $this->info("Transforming {$className}...");

            if ($outputPath) {
                $filePath = $transformer->transformToFile($className, $outputPath);
            } else {
                $filePath = $transformer->transformToFile($className);
            }

            $this->info("✅ Successfully transformed {$className}");
            $this->line("   Generated: {$filePath}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to transform {$className}: ".$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function handleDiscovery(DartTransformer $transformer): int
    {
        $this->info('Discovering and transforming classes...');

        try {
            $transformedFiles = $transformer->discoverAndTransform();

            if (empty($transformedFiles)) {
                $this->warn('No applicable classes found for transformation');

                return self::SUCCESS;
            }

            $this->info('✅ Successfully transformed '.count($transformedFiles).' classes:');

            foreach ($transformedFiles as $file) {
                $this->line("   - {$file}");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Discovery failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
