# Dart Transformer

<!-- [![Latest Version on Packagist](https://img.shields.io/packagist/v/2rius/dart-transformer.svg?style=flat-square)](https://packagist.org/packages/2rius/dart-transformer) -->
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/2rius/dart-transformer/run-tests.yml?branch=main&label=Tests&style=flat-square)](https://github.com/2rius/dart-transformer/actions/workflows/run-tests.yml)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/2rius/dart-transformer/fix-php-code-style-issues.yml?branch=main&label=Code%20style&style=flat-square)](https://github.com/2rius/dart-transformer/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Github PHPStan Action Status](https://img.shields.io/github/actions/workflow/status/2rius/dart-transformer/phpstan.yml?branch=main&label=PHPStan&style=flat-square)](https://github.com/2rius/dart-transformer/actions/workflows/phpstan.yml)
<!-- [![Total Downloads](https://img.shields.io/packagist/dt/2rius/dart-transformer.svg?style=flat-square)](https://packagist.org/packages/2rius/dart-transformer) -->

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
    // The paths where dart-transformer will look for PHP classes to transform.
    'auto_discover_types' => [
        app_path(),
    ],

    // Collectors decide which classes should be transformed.
    'collectors' => [
        M2rius\DartTransformer\Collectors\DefaultCollector::class,
        M2rius\DartTransformer\Collectors\EnumCollector::class,
    ],

    // Transformers take PHP classes and output their Dart representation.
    'transformers' => [
        'data_classes' => \M2rius\DartTransformer\Transformers\DataClassTransformer::class,
        'enums' => \M2rius\DartTransformer\Transformers\EnumTransformer::class,
    ],

    // Default type replacements for PHP types to Dart types.
    'default_type_replacements' => [
        DateTimeInterface::class => 'String',
        DateTimeImmutable::class => 'String',
        DateTime::class => 'String',
        Carbon\CarbonInterface::class => 'String',
        Carbon\CarbonImmutable::class => 'String',
        Carbon\Carbon::class => 'String',
    ],

    // The package will write the generated Dart to this file.
    'output_file' => resource_path('dart/generated.dart'),

    // Optionally configure a formatter (none by default)
    'formatter' => null,

    // Generate native Dart enums or string constants
    'transform_to_native_enums' => true,

    // Dart-specific options
    'dart' => [
        'use_nullable_types' => true,
        'use_json_annotation' => true,
        'header' => null,
    ],
];
```

## Usage

### Generate aggregated Dart definitions

Run the command to generate a single aggregated file with all transformable classes and enums:

```bash
php artisan dart:transform
```

By default this writes to `resources/dart/generated.dart`.

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

```php
use M2rius\DartTransformer\DartTransformer;

$transformer = app(DartTransformer::class);

// Generate aggregated file; optionally pass an explicit list of classes
$result = $transformer->generate();
// ['path' => 'resources/dart/generated.dart', 'count' => 42]
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

## Credits

- [Max-Emil Thorius](https://github.com/2rius)
- [All Contributors](../../contributors)
- Inspired by [Spatie's TypeScript Transformer](https://github.com/spatie/typescript-transformer)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
