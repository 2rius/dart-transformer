<?php

namespace M2rius\DartTransformer\Naming;

interface NamingStrategy
{
    /**
     * Transform a PHP Fully Qualified Class Name (e.g. App\Models\User) into a Dart-friendly class name.
     */
    public function transform(string $fqcn): string;
}


