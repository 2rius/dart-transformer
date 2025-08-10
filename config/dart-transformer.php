<?php

// config for M2rius/DartTransformer
return [
    /*
     * Automatically discover and transform classes that match these patterns
     */
    'auto_discover' => [
        'data' => [
            'enabled' => true,
            'paths' => [
                'app/Data',
            ],
        ],
        'enums' => [
            'enabled' => true,
            'paths' => [
                'app/Enums',
            ],
        ],
    ],

    /*
     * Output settings
     */
    'output' => [
        // Directory where the aggregated file will be written
        'path' => 'resources/dart',
        // Name of the aggregated file
        'file' => 'generated.dart',
    ],

    /*
     * Transformation options
     */
    'transformers' => [
        'data_classes' => \M2rius\DartTransformer\Transformers\DataClassTransformer::class,
        'enums' => \M2rius\DartTransformer\Transformers\EnumTransformer::class,
    ],

    /*
     * Dart specific options
     */
    'dart' => [
        'use_nullable_types' => true,
        'use_json_annotation' => true,
        // You can set a header banner or leave null for default
        'header' => null,
    ],
];
