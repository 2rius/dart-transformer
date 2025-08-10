<?php

namespace M2rius\DartTransformer\Commands;

use Illuminate\Console\Command;
use M2rius\DartTransformer\DartTransformer;

class DartTransformerCommand extends Command
{
    public $signature = 'dart:transform';

    public $description = 'Generate a single aggregated Dart file with all transformable PHP data classes and enums';

    public function handle(): int
    {
        $transformer = app(DartTransformer::class);

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
