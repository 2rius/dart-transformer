<?php

namespace M2rius\DartTransformer\Formatters;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DartFormatter implements Formatter
{
    public function format(string $file): void
    {
        $process = new Process(['dart', 'format', $file]);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
