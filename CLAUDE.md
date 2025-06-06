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
- `TranslateService::multiple()`: Batch translation to multiple target languages (supports both v2 and v3)

### Important Implementation Details

- `multiple()` method now supports both v2 and v3 clients based on config setting
- v3 client includes Chinese error messages and uses `die()` for fatal errors
- v2 client handles multiple translations by iterating through individual translate calls
- Both clients support SSL verification toggle via config
- Error handling differs between v2 (returns arrays) and v3 (returns false + stores errors)

### LocaleTranslation Class

New class `LocaleTranslation` provides functionality to scan Laravel language directories and translate them:
- Scans all PHP files in source locale directory
- Translates content to multiple target locales using existing Google Translate service
- **Uses batch translation (`Translate::multiple()`) for improved performance**
- Supports overwrite protection for existing translations
- Handles nested array structures properly
- Creates target locale directories automatically
- Generates properly formatted PHP array files
- Preserves source key order in translated files

### Testing

- PHPUnit test suite with 58 tests covering all functionality
- Tests are framework-agnostic and can run without full Laravel environment
- Use `./vendor/bin/phpunit` to run all tests
- Test functions use lowerCamelCase naming convention
- Includes specific tests for multiple() method compatibility with both API versions
- Includes tests for accurate translation counting in LocaleTranslation
- Includes tests for key order preservation in translated files
- Includes tests for progress bar functionality in translate:lang command
- Includes tests for batch translation performance optimization