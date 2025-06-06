<?php

namespace FF\GoogleTranslate\LocaleTranslation;

use FF\GoogleTranslate\Facade\Translate;
use Illuminate\Support\Facades\File;

class LocaleTranslation
{
    protected string $langPath;

    public function __construct(?string $langPath = null)
    {
        $this->langPath = $langPath ?? $this->getDefaultLangPath();
    }

    protected function getDefaultLangPath(): string
    {
        if (function_exists('base_path')) {
            try {
                return base_path('lang');
            } catch (\Error|\Exception $e) {
                // base_path() exists but Laravel app not properly initialized
            }
        }
        return getcwd() . '/lang';
    }

    public function translate(string $sourceLocale, array $targetLocales, bool $overwrite = false): array
    {
        $results = [];
        
        if (!is_dir($this->langPath)) {
            throw new \InvalidArgumentException("Lang directory does not exist: {$this->langPath}");
        }

        $sourceLocalePath = $this->langPath . DIRECTORY_SEPARATOR . $sourceLocale;
        if (!is_dir($sourceLocalePath)) {
            throw new \InvalidArgumentException("Source locale directory does not exist: {$sourceLocalePath}");
        }

        $sourceFiles = $this->scanLangFiles($sourceLocalePath);
        
        foreach ($targetLocales as $targetLocale) {
            $targetLocalePath = $this->langPath . DIRECTORY_SEPARATOR . $targetLocale;
            
            if (!is_dir($targetLocalePath)) {
                $this->makeDirectory($targetLocalePath, 0755, true);
            }

            $results[$targetLocale] = $this->translateLocaleFiles($sourceFiles, $sourceLocalePath, $targetLocalePath, $sourceLocale, $targetLocale, $overwrite);
        }

        return $results;
    }

    protected function scanLangFiles(string $localePath): array
    {
        $files = [];
        $phpFiles = $this->glob($localePath . '/*.php');
        
        foreach ($phpFiles as $file) {
            $fileName = basename($file);
            $files[$fileName] = $file;
        }

        return $files;
    }

    protected function glob(string $pattern): array
    {
        if (class_exists('Illuminate\Support\Facades\File') && $this->isFacadeAvailable()) {
            return File::glob($pattern);
        }
        return glob($pattern) ?: [];
    }

    protected function isFacadeAvailable(): bool
    {
        try {
            return class_exists('Illuminate\Support\Facades\Facade') && 
                   method_exists('Illuminate\Support\Facades\Facade', 'getFacadeRoot') &&
                   \Illuminate\Support\Facades\Facade::getFacadeApplication() !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function translateLocaleFiles(array $sourceFiles, string $sourceLocalePath, string $targetLocalePath, string $sourceLocale, string $targetLocale, bool $overwrite): array
    {
        $results = [];

        foreach ($sourceFiles as $fileName => $sourceFilePath) {
            $targetFilePath = $targetLocalePath . DIRECTORY_SEPARATOR . $fileName;
            
            try {
                $sourceTranslations = include $sourceFilePath;
                if (!is_array($sourceTranslations)) {
                    $results[$fileName] = ['status' => 'skipped', 'reason' => 'Invalid PHP array structure'];
                    continue;
                }

                $existingTranslations = [];
                if (file_exists($targetFilePath) && !$overwrite) {
                    $existingTranslations = include $targetFilePath;
                    if (!is_array($existingTranslations)) {
                        $existingTranslations = [];
                    }
                }

                $translationResult = $this->translateArrayWithCount($sourceTranslations, $existingTranslations, $sourceLocale, $targetLocale, $overwrite);
                
                $this->writeTranslationFile($targetFilePath, $translationResult['data']);
                
                $results[$fileName] = ['status' => 'success', 'translated_keys' => $translationResult['translated_count']];
                
            } catch (\Exception $e) {
                $results[$fileName] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    protected function translateArrayWithCount(array $sourceArray, array $existingArray, string $sourceLocale, string $targetLocale, bool $overwrite): array
    {
        $result = [];
        $translatedCount = 0;

        // Iterate through source array to preserve key order
        foreach ($sourceArray as $key => $value) {
            if (is_array($value)) {
                $existingSubArray = isset($existingArray[$key]) && is_array($existingArray[$key]) ? $existingArray[$key] : [];
                $subResult = $this->translateArrayWithCount($value, $existingSubArray, $sourceLocale, $targetLocale, $overwrite);
                $result[$key] = $subResult['data'];
                $translatedCount += $subResult['translated_count'];
            } else {
                if ($overwrite || !isset($existingArray[$key]) || empty($existingArray[$key])) {
                    try {
                        $translatedValue = Translate::get($value, $targetLocale, $sourceLocale);
                        $result[$key] = $translatedValue;
                        $translatedCount++; // Increment only when actually translated
                    } catch (\Exception $e) {
                        $result[$key] = $value;
                        // Don't increment count for failed translations
                    }
                } else {
                    // Use existing value but preserve source order
                    $result[$key] = $existingArray[$key];
                }
            }
        }

        // Add any keys from existing array that are not in source array
        // These will be appended at the end to preserve existing translations
        foreach ($existingArray as $key => $value) {
            if (!array_key_exists($key, $result)) {
                $result[$key] = $value;
            }
        }

        return [
            'data' => $result,
            'translated_count' => $translatedCount
        ];
    }

    protected function translateArray(array $sourceArray, array $existingArray, string $sourceLocale, string $targetLocale, bool $overwrite): array
    {
        $result = $this->translateArrayWithCount($sourceArray, $existingArray, $sourceLocale, $targetLocale, $overwrite);
        return $result['data'];
    }

    protected function makeDirectory(string $path, int $mode = 0755, bool $recursive = false): bool
    {
        if (class_exists('Illuminate\Support\Facades\File') && $this->isFacadeAvailable()) {
            return File::makeDirectory($path, $mode, $recursive);
        }
        return mkdir($path, $mode, $recursive);
    }

    protected function writeTranslationFile(string $filePath, array $data): void
    {
        $content = "<?php\n\nreturn " . $this->arrayToPhpString($data) . ";\n";
        $this->putFile($filePath, $content);
    }

    protected function putFile(string $path, string $content): bool
    {
        if (class_exists('Illuminate\Support\Facades\File') && $this->isFacadeAvailable()) {
            return File::put($path, $content);
        }
        return file_put_contents($path, $content) !== false;
    }

    protected function arrayToPhpString(array $array, int $depth = 0): string
    {
        $indent = str_repeat('    ', $depth);
        $items = [];

        foreach ($array as $key => $value) {
            $keyString = is_string($key) ? "'" . addslashes($key) . "'" : $key;
            
            if (is_array($value)) {
                $valueString = $this->arrayToPhpString($value, $depth + 1);
                $items[] = "{$indent}    {$keyString} => {$valueString}";
            } else {
                $valueString = "'" . addslashes($value) . "'";
                $items[] = "{$indent}    {$keyString} => {$valueString}";
            }
        }

        return "[\n" . implode(",\n", $items) . "\n{$indent}]";
    }

    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . '.' . $key;
            
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        
        return $result;
    }
}