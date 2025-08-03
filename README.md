# Dart Transformer

[![Latest Version on Packagist](https://img.shields.io/packagist/v/2rius/dart-transformer.svg?style=flat-square)](https://packagist.org/packages/2rius/dart-transformer)
[![Tests](https://img.shields.io/github/actions/workflow/status/2rius/dart-transformer/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/2rius/dart-transformer/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/2rius/dart-transformer.svg?style=flat-square)](https://packagist.org/packages/2rius/dart-transformer)

Laravel package for converting PHP classes and types to Dart equivalents, with special support for [Spatie Laravel Data](https://spatie.be/docs/laravel-data/v4/introduction) classes and PHP enums.

This package is heavily inspired by [Spatie's TypeScript Transformer](https://spatie.be/docs/typescript-transformer/v2/introduction), but targets Dart instead of TypeScript.

## Features

- 🚀 Transform Spatie Laravel Data classes to Dart classes
- 🎯 Convert PHP enums to Dart enums
- 📦 Generate JSON serialization code with `json_annotation`
- 🔧 Configurable output paths and transformers
- 🎨 Smart type mapping from PHP to Dart
- ⚡ Artisan command for easy transformation

## Installation

You can install the package via composer:

```bash
composer require 2rius/dart-transformer
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="dart-transformer-config"
```

This is the contents of the published config file:

```php
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
```

## Usage

### Transform a specific class

```bash
php artisan dart:transform App\\Data\\UserData
```

### Auto-discover and transform all applicable classes

```bash
php artisan dart:transform --discover
```

### Specify custom output directory

```bash
php artisan dart:transform App\\Data\\UserData --output=lib/models
```

### Example Transformations

#### Laravel Data Class

PHP:

```php
<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $email,
        public bool $isActive
    ) {}
}
```

Generated Dart:

```dart
import 'package:json_annotation/json_annotation.dart';

part 'user_data.g.dart';

@JsonSerializable()
class UserData {
  final int id;
  final String name;
  final String? email;
  final bool isActive;

  const UserData({
    required this.id,
    required this.name,
    required this.email,
    required this.isActive,
  });

  factory UserData.fromJson(Map<String, dynamic> json) => _$UserDataFromJson(json);

  Map<String, dynamic> toJson() => _$UserDataToJson(this);
}
```

#### PHP Enum

PHP:

```php
<?php

namespace App\Enums;

enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}
```

Generated Dart:

```dart
import 'package:json_annotation/json_annotation.dart';

enum Status {
  @JsonValue('active')
  ACTIVE,
  @JsonValue('inactive')
  INACTIVE,
  @JsonValue('pending')
  PENDING,
}
```

### Programmatic Usage

You can also use the transformer programmatically:

```php
use M2rius\DartTransformer\DartTransformer;

$transformer = app(DartTransformer::class);

// Transform a class and get the Dart code
$dartCode = $transformer->transform(App\Data\UserData::class);

// Transform and save to file
$filePath = $transformer->transformToFile(App\Data\UserData::class);

// Auto-discover and transform multiple classes
$transformedFiles = $transformer->discoverAndTransform();
```

## Configuration

The package provides several configuration options:

- **auto_discover**: Configure which directories to scan for classes
- **output**: Set the output directory and file extension
- **transformers**: Customize which transformers to use
- **dart**: Dart-specific options like nullable types and JSON annotations

## Type Mapping

| PHP Type | Dart Type |
|----------|-----------|
| `int` | `int` |
| `float`, `double` | `double` |
| `string` | `String` |
| `bool` | `bool` |
| `array` | `List<dynamic>` |
| `object` | `Map<String, dynamic>` |
| `mixed` | `dynamic` |
| Custom classes | Preserved as-is |

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Max-Emil Thorius](https://github.com/2rius)
- [All Contributors](../../contributors)
- Inspired by [Spatie's TypeScript Transformer](https://github.com/spatie/typescript-transformer)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
