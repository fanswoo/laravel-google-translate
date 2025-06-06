# Fanswoo/Laravel Google Translate

A comprehensive Laravel package that provides Google Translate functionality with support for both API v2 and v3, plus automated language file translation capabilities.

## Features

- **Google Translate API Integration**: Support for both v2 (API key) and v3 (service account) authentication
- **Language File Translation**: Automatically translate Laravel language files using `translate:lang` command
- **Facade Support**: Easy-to-use Laravel facade for translation operations
- **Batch Translation**: Translate multiple texts to multiple target languages
- **Overwrite Protection**: Preserve existing translations while adding new ones
- **Nested Array Support**: Handle complex Laravel language file structures

## Requirements

- Laravel v11
- PHP ^8.4

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

## Configuration

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

### Basic Translation

```php
use FF\GoogleTranslate\Facade\Translate;

// Single text translation
$text = Translate::get('hello world', 'zh_TW');

// Multiple texts to multiple languages
$translations = Translate::multiple(['hello', 'world'], ['zh_TW', 'ja_JP']);
```

### Language File Translation Command

Automatically translate all your Laravel language files:

```bash
# Translate from English to Traditional Chinese and Japanese
php artisan translate:lang en zh_TW ja_JP

# Overwrite existing translations
php artisan translate:lang en zh_TW --overwrite

# Use custom language directory path
php artisan translate:lang en fr_FR --path=/custom/lang/path
```

**Command Options:**
- `source`: Source locale (e.g., en)
- `targets`: One or more target locales (e.g., zh_TW ja_JP fr_FR)
- `--overwrite`: Overwrite existing translations (default: false)
- `--path`: Custom path to language directory (default: Laravel's lang directory)

### Programmatic Language File Translation

```php
use FF\GoogleTranslate\LocaleTranslation\LocaleTranslation;

$localeTranslation = new LocaleTranslation();

// Translate from 'en' to multiple target locales
$results = $localeTranslation->translate('en', ['zh_TW', 'ja_JP'], false);

// With custom path and overwrite enabled
$results = $localeTranslation->translate('en', ['fr_FR'], true);
```

## API Version Configuration

### Google Translate API v2 (Default)
- Simple API key authentication
- Faster setup
- Suitable for basic translation needs

### Google Translate API v3
- Service account authentication
- More secure and feature-rich
- Required for batch operations via `multiple()` method

The package automatically uses v3 for the `multiple()` method regardless of the configured version.

## Testing

Run the test suite:

```bash
./vendor/bin/phpunit
```

The package includes comprehensive tests for:
- Basic translation functionality
- Language file scanning and translation
- Command structure validation
- Error handling and edge cases

## Example Language File Structure

The package handles Laravel's standard language file structure:

```php
// lang/en/messages.php
return [
    'welcome' => 'Welcome to our application',
    'goodbye' => 'Goodbye!',
    'auth' => [
        'failed' => 'These credentials do not match our records.',
        'password' => 'The provided password is incorrect.',
    ],
];
```

After running `php artisan translate:lang en zh_TW`, you'll get:

```php
// lang/zh_TW/messages.php
return [
    'welcome' => '歡迎使用我們的應用程式',
    'goodbye' => '再見！',
    'auth' => [
        'failed' => '這些憑據與我們的記錄不符。',
        'password' => '提供的密碼不正確。',
    ],
];
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
