<?php

// config for M2rius/DartTransformer
return [
    /*
     * The paths where dart-transformer will look for PHP classes to transform.
     * Defaults to the Laravel app path.
     */
    'auto_discover_types' => [
        function_exists('app_path') ? app_path() : 'app',
    ],

    /*
     * Collectors will search for classes in the `auto_discover_types` paths and
     * decide which classes should be transformed.
     */
    'collectors' => [
        \M2rius\DartTransformer\Collectors\DefaultCollector::class,
        \M2rius\DartTransformer\Collectors\EnumCollector::class,
    ],

    /*
     * Transformers take PHP classes as input and will output a Dart representation.
     */
    'transformers' => [
        'data_classes' => \M2rius\DartTransformer\Transformers\DataClassTransformer::class,
        'enums' => \M2rius\DartTransformer\Transformers\EnumTransformer::class,
    ],

    /*
     * Default type replacements map PHP types to Dart types.
     */
    'default_type_replacements' => [
        \DateTimeInterface::class => 'String',
        \DateTimeImmutable::class => 'String',
        \DateTime::class => 'String',
        \Carbon\CarbonInterface::class => 'String',
        \Carbon\CarbonImmutable::class => 'String',
        \Carbon\Carbon::class => 'String',
    ],

    /*
     * The package will write the generated Dart to this file.
     */
    'output_file' => function_exists('resource_path')
        ? resource_path('dart/generated.dart')
        : 'resources/dart/generated.dart',

    /*
     * The generated Dart file can be formatted. Provide a formatter class name
     * if desired; Dart formatter is used by default.
     */
    'formatter' => \M2rius\DartTransformer\Formatters\DartFormatter::class,

    /*
     * Enums can be generated as native Dart enums or as a class with string constants.
     */
    'transform_to_native_enums' => false,

    /*
     * Dart-specific options.
     */
    'dart' => [
        'use_nullable_types' => true,
        'use_json_annotation' => true,
        'header' => null,
    ],
];
