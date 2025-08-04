<?php

namespace M2rius\DartTransformer\Commands;

use Illuminate\Console\Command;
use M2rius\DartTransformer\DartTransformer;

class DartTransformerCommand extends Command
{
    public $signature = 'dart:transform
                        {class? : The specific class to transform}
                        {--output= : Output path for generated Dart file(s)}
                        {--discover : Automatically discover and transform all applicable classes}
                        {--mode=single : Output mode: single (consolidated file) or separate (individual files)}
                        {--filename=generated.dart : Filename for single file mode}';

    public $description = 'Transform PHP classes to Dart equivalents with consolidated output';

    public function handle(): int
    {
        $transformer = app(DartTransformer::class);

        $className = $this->argument('class');
        $outputPath = $this->option('output');
        $discover = $this->option('discover');
        $mode = $this->option('mode') ?? 'single';
        $filename = $this->option('filename');

        // Override config with command options if needed
        if ($mode !== 'single' || $filename || $outputPath) {
            $config = array_merge($transformer->config ?? [], [
                'output' => array_merge($transformer->config['output'] ?? [], [
                    'mode' => $mode,
                    'filename' => $filename,
                ]),
            ]);

            if ($outputPath) {
                $config['output']['path'] = $outputPath;
            }

            $transformer = new \M2rius\DartTransformer\DartTransformer($config);
        }

        if ($discover) {
            return $this->handleDiscovery($transformer, $mode);
        }

        if ($className) {
            return $this->handleSingleClass($transformer, $className, $outputPath, $mode);
        }

        $this->info('Please specify a class to transform or use --discover to transform all applicable classes');
        $this->line('');
        $this->line('Examples:');
        $this->line('  php artisan dart:transform --discover');
        $this->line('  php artisan dart:transform --discover --mode=separate');
        $this->line('  php artisan dart:transform App\\Data\\UserData');
        $this->line('  php artisan dart:transform App\\Data\\UserData --mode=separate');
        $this->line('  php artisan dart:transform --discover --output=lib/models --filename=types.dart');

        return self::SUCCESS;
    }

    protected function handleSingleClass(DartTransformer $transformer, string $className, ?string $outputPath, string $mode): int
    {
        try {
            if (! class_exists($className) && ! enum_exists($className)) {
                $this->error("Class {$className} does not exist");

                return self::FAILURE;
            }

            $this->info("Transforming {$className}...");

            if ($mode === 'single') {
                // Use consolidated transformation for single mode
                $filePath = $transformer->transformAllToFile([$className], $outputPath);
                $this->info("✅ Successfully transformed {$className} to consolidated file");
            } else {
                // Use individual file transformation
                if ($outputPath) {
                    $filePath = $transformer->transformToFile($className, $outputPath);
                } else {
                    $filePath = $transformer->transformToFile($className);
                }
                $this->info("✅ Successfully transformed {$className}");
            }

            $this->line("   Generated: {$filePath}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to transform {$className}: ".$e->getMessage());

            return self::FAILURE;
        }
    }

    protected function handleDiscovery(DartTransformer $transformer, string $mode): int
    {
        $this->info('Discovering and transforming classes...');

        try {
            $transformedFiles = $transformer->discoverAndTransform();

            if (empty($transformedFiles)) {
                $this->warn('No applicable classes found for transformation');

                return self::SUCCESS;
            }

            if ($mode === 'single') {
                $this->info('✅ Successfully consolidated all discovered classes into 1 file:');
            } else {
                $this->info('✅ Successfully transformed '.count($transformedFiles).' classes:');
            }

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
