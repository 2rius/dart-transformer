<?php

namespace M2rius\DartTransformer\Naming;

class FqcnUnderscoredNamingStrategy implements NamingStrategy
{
    public function transform(string $fqcn): string
    {
        return str_replace('\\', '_', ltrim($fqcn, '\\'));
    }
}
