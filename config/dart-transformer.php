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
        'path' => 'resources/dart',
        'extension' => '.dart',
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
        'package_name' => null, // Auto-detect from pubspec.yaml if null
    ],
];
