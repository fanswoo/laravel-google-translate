<?php

namespace FF\GoogleTranslate;

use FF\GoogleTranslate\Facade\TranslateService;
use FF\GoogleTranslate\GoogleTranslateClient\GoogleTranslateClient2;
use FF\GoogleTranslate\GoogleTranslateClient\GoogleTranslateClient3;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class GoogleTranslateProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->app->bind('laravel-google-translate', function ($app) {
            return new TranslateService();
        });

        $this->mergeConfigFrom(
            __DIR__.'/../config/google-translate.php', 'google-translate'
        );
        $this->publishes([
            __DIR__.'/../config/google-translate.php' => config_path('google-translate.php'),
        ], 'config');
    }
}
