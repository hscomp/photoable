<?php

namespace Hscomp\Photoable;

use Illuminate\Support\ServiceProvider;

class PhotoableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/routes.php');

        $this->loadViewsFrom(__DIR__ . '/views', 'hscomp/photo-uploader');

        $this->loadTranslationsFrom(__DIR__ . '/translations', 'hscomp/photo-uploader');

        //$this->loadMigrationsFrom(__DIR__ . '/migrations');

        $this->publishes([
            __DIR__ . '/views' => resource_path('views/vendor/hscomp/photo-uploader'),
            __DIR__ . '/config/photouploader.php' => config_path('photouploader.php'),
            __DIR__ . '/translations' => resource_path('lang/vendor/hscomp/photo-uploader'),
            __DIR__ . '/migrations/2014_10_12_000000_create_photouploader_table.php' => database_path('migrations/2014_10_12_000000_create_photouploader_table.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('Hscomp\PhotoUploader\PhotoUploaderController');

        $this->mergeConfigFrom(__DIR__ . '/config/photouploader.php', 'photouploader');
    }

}
