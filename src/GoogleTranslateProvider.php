<?php

namespace FF\Localization;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LocalizationProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        Route::get('/fanswoo/localization.js', LocalizationController::class);
    }
}
