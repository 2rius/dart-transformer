<?php

namespace M2rius\DartTransformer;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use M2rius\DartTransformer\Commands\DartTransformerCommand;

class DartTransformerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('dart-transformer')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_dart_transformer_table')
            ->hasCommand(DartTransformerCommand::class);
    }
}
