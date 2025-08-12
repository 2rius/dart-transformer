# Dart Transformer

[![Latest Version on Packagist](https://img.shields.io/packagist/v/2rius/dart-transformer.svg?style=flat-square)](https://packagist.org/packages/2rius/dart-transformer)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/2rius/dart-transformer/run-tests.yml?branch=main&label=run-tests&style=flat-square)](https://github.com/2rius/dart-transformer/actions/workflows/run-tests.yml)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/2rius/dart-transformer/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/2rius/dart-transformer/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Github PHPStan Action Status](https://img.shields.io/github/actions/workflow/status/2rius/dart-transformer/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/2rius/dart-transformer/actions/workflows/phpstan.yml)
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

## Configuration

The package provides several configuration options:

- **auto_discover_types**: Configure which directories to scan for classes
- **collectors**: Customize which collectors to use
- **transformers**: Customize which transformers to use
- **default_type_replacements**: Configure default type replacements when mapping from PHP to Dart types.
- **output_file**: Set the output file
- **formatter**: Format the generated Dart file.
- **dart**: Dart-specific options like nullable types and JSON annotations

### Class naming and collisions

Dart does not have PHP-style namespaces inside a single file. When generating a single aggregated Dart file, two PHP classes that share the same short class name (e.g. `App\Data\UserData` and `App\Admin\UserData`) will collide.

- By default we use the short class name via `ShortClassNamingStrategy`.
- If you run into collisions, either:
  - switch to `FqcnUnderscoredNamingStrategy` in your config to produce names like `App_Admin_UserData`, or
  - implement your own strategy by implementing `M2rius\\DartTransformer\\Naming\\NamingStrategy` and set it in `dart.naming_strategy`.

Example config:

```php
'dart' => [
    'naming_strategy' => \M2rius\DartTransformer\Naming\FqcnUnderscoredNamingStrategy::class,
],
```

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
