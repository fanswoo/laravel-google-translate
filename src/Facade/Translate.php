<?php

namespace FF\GoogleTranslate\Facade;

use Illuminate\Support\Facades\Facade;

class Translate extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-google-translate';
    }
}