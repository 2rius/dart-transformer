<?php

namespace M2rius\DartTransformer\Naming;

class ShortClassNamingStrategy implements NamingStrategy
{
    public function transform(string $fqcn): string
    {
        $position = strrpos($fqcn, '\\');
        if ($position === false) {
            return $fqcn;
        }

        return substr($fqcn, $position + 1);
    }
}
