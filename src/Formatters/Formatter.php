<?php

namespace M2rius\DartTransformer\Formatters;

interface Formatter
{
    public function format(string $file): void;
}


