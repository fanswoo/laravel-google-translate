## Package Overview
This is a Laravel package (`fanswoo/laravel-google-translate`) that provides Google Translate functionality with support for both Google Translate API v2 and v3. The package follows Laravel package conventions and provides a facade for easy usage.

## Development Commands
Since this is a Laravel package library:
- Use `composer install` to install dependencies
- Package development typically involves testing within a Laravel application
- Run tests with `./vendor/bin/phpunit`

## Architecture

### Core Components

**Service Provider (`GoogleTranslateProvider.php`)**
- Registers the `TranslateService` as 'laravel-google-translate' in the container
- Publishes the config file to Laravel applications
- Merges package config with application config

**Facade Layer**
- `Translate.php`: Laravel facade that proxies to the registered service
- `TranslateService.php`: Main service class with `get()` and `multiple()` methods

**Client Implementation**
- `GoogleTranslateClient2.php`: Handles Google Translate API v2 using API key authentication
- `GoogleTranslateClient3.php`: Handles Google Translate API v3 using service account credentials

### API Version Handling

The package supports both Google Translate API versions:
- **v2**: Uses API key authentication, simpler setup
- **v3**: Uses service account JSON credentials, more secure

Version selection is controlled by `config('google-translate.api_version')` defaulting to 'v2'.

### Configuration Structure

The package expects two configuration sources:
1. `config/google-translate.php` - Package-specific config (SSL verification, API version)
2. `config/services.php` - Laravel services config containing API credentials

### Key Methods

- `TranslateService::get()`: Single text translation with optional source language detection
- `TranslateService::multiple()`: Batch translation to multiple target languages (v3 only)

### Important Implementation Details

- `multiple()` method always uses v3 client regardless of config setting
- v3 client includes Chinese error messages and uses `die()` for fatal errors
- Both clients support SSL verification toggle via config
- Error handling differs between v2 (returns arrays) and v3 (returns false + stores errors)

### LocaleTranslation Class

New class `LocaleTranslation` provides functionality to scan Laravel language directories and translate them:
- Scans all PHP files in source locale directory
- Translates content to multiple target locales using existing Google Translate service
- Supports overwrite protection for existing translations
- Handles nested array structures properly
- Creates target locale directories automatically
- Generates properly formatted PHP array files

### Testing

- PHPUnit test suite with 20 tests covering all functionality
- Tests are framework-agnostic and can run without full Laravel environment
- Use `./vendor/bin/phpunit` to run all tests
- Test functions use lowerCamelCase naming convention