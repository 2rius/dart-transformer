<?php

namespace M2rius\DartTransformer;

use M2rius\DartTransformer\Commands\DartTransformerCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
            ->hasCommand(DartTransformerCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(DartTransformer::class, fn () => new DartTransformer(config('dart-transformer', [])));
    }
}
