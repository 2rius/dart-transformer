<?php

namespace M2rius\DartTransformer\Commands;

use Illuminate\Console\Command;
use M2rius\DartTransformer\DartTransformer;

class DartTransformerCommand extends Command
{
    public $signature = 'dart:transform
                            {--path= : Specify a path with classes to transform}
                            {--output= : Use another file to output}
                            {--format : Use Dart formatter to format the output}';

    public $description = 'Generate a single aggregated Dart file with all transformable PHP data classes and enums';

    public function handle(): int
    {
        // Read CLI options
        $pathOption = (string) ($this->option('path') ?? '');
        $outputOption = (string) ($this->option('output') ?? '');
        $formatOption = (bool) ($this->option('format'));

        // Build config overrides only when options are provided
        $overrides = [];
        if ($pathOption !== '') {
            $paths = array_values(array_filter(array_map('trim', preg_split('/[,;|]/', $pathOption))));
            if (! empty($paths)) {
                $overrides['auto_discover_types'] = $paths;
            }
        }
        if ($outputOption !== '') {
            $overrides['output_file'] = $outputOption;
        }
        if ($formatOption) {
            $overrides['formatter'] = \M2rius\DartTransformer\Formatters\DartFormatter::class;
        }

        // Use container-resolved instance when no overrides (supports test mocking)
        // Otherwise instantiate a local transformer with merged config
        if (! empty($overrides)) {
            $baseConfig = function_exists('config') ? (array) config('dart-transformer', []) : [];
            $config = array_replace_recursive($baseConfig, $overrides);
            $transformer = new DartTransformer($config);
        } else {
            $transformer = app(DartTransformer::class);
        }

        $this->info('Generating Dart definitions...');

        try {
            $result = $transformer->generate();
            $count = (int) ($result['count'] ?? 0);
            $path = (string) ($result['path'] ?? '');

            if ($count === 0) {
                $this->warn('No applicable classes found for transformation');

                return self::SUCCESS;
            }

            $this->info("✅ Successfully generated {$count} definitions: {$path}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Generation failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
