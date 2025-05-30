# Fanswoo/Laravel Google Translate

## Fanswoo/Laravel Google Translate

This package provides Google Translate instance. it can also support Filament forms field.

### Requirements

-   Laravel v11

## Installation

You can install the package via composer:

```bash
composer require fanswoo/laravel-google-translate
```

After that run the `vendor:publish` command:

```bash
php artisan vendor:publish --provider=FF\\GoogleTranslate\\GoogleTranslateProvider --tag=config
```

This will publish config from `fanswoo/laravel-google-translate`

## Set config file

```php
// config/services.php
[
    'google_translate_v2' => [
        'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),
    ],
    'google_translate_v3' => [
        'project_id' => env('GOOGLE_TRANSLATE_PROJECT_ID'),
        'service_account_credentials_path' => env('GOOGLE_TRANSLATE_SERVICE_ACCOUNT_CREDENTIALS_PATH'),
    ],
],
```

```php
// config/google-translate.php
return [
    // SSL verification for cURL requests
    'ssl_verify_peer' => true,
    
    // Google Cloud Version, can be 'v2' or 'v3'
    'api_version' => 'v2'
];
```

## Usage

### Files usage

```php
use FF\GoogleTranslate\Facade\Translate;

$text = Translate::get('hello world', 'zh_TW');
$text = Translate::multiple(['hello', 'world'], ['zh_TW', 'ja_JP']);
```
