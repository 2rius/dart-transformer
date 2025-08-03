<?php

namespace M2rius\DartTransformer\Commands;

use Illuminate\Console\Command;

class DartTransformerCommand extends Command
{
    public $signature = 'dart-transformer';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
