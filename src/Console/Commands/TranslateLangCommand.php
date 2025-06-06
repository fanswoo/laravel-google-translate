<?php

namespace FF\GoogleTranslate\Console\Commands;

use FF\GoogleTranslate\LocaleTranslation\LocaleTranslation;
use Illuminate\Console\Command;

class TranslateLangCommand extends Command
{
    protected $signature = 'translate:lang 
                            {source : Source locale (e.g., en)}
                            {targets* : Target locales (e.g., zh_TW ja_JP)}
                            {--overwrite : Overwrite existing translations}
                            {--path= : Custom path to lang directory}';

    protected $description = 'Translate language files from source locale to target locales using Google Translate';

    public function handle(): int
    {
        $sourceLocale = $this->argument('source');
        $targetLocales = $this->argument('targets');
        $overwrite = $this->option('overwrite');
        $customPath = $this->option('path');

        if (empty($targetLocales)) {
            $this->error('At least one target locale must be specified.');
            return self::FAILURE;
        }

        $this->info("Translating from '{$sourceLocale}' to: " . implode(', ', $targetLocales));
        
        if ($overwrite) {
            $this->warn('Overwrite mode enabled - existing translations will be replaced.');
        }

        try {
            $localeTranslation = new LocaleTranslation($customPath);
            
            // Pre-scan source files to calculate total work
            $sourceFiles = $this->scanSourceFiles($localeTranslation, $sourceLocale);
            $totalFiles = count($sourceFiles) * count($targetLocales);
            
            if ($totalFiles === 0) {
                $this->warn('No language files found in source locale.');
                return self::SUCCESS;
            }
            
            $this->info("Found " . count($sourceFiles) . " source files. Total translations: {$totalFiles}");
            
            $progressBar = $this->output->createProgressBar($totalFiles);
            $progressBar->start();

            $results = [];
            foreach ($targetLocales as $targetLocale) {
                $result = $localeTranslation->translate($sourceLocale, [$targetLocale], $overwrite);
                $results[$targetLocale] = $result[$targetLocale];
                
                // Advance progress bar by number of files processed for this locale
                $progressBar->advance(count($sourceFiles));
            }

            $progressBar->finish();
            $this->newLine(2);

            $this->displayResults($results);

            return self::SUCCESS;

        } catch (\InvalidArgumentException $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error("Unexpected error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    protected function displayResults(array $results): void
    {
        $this->info('Translation Results:');
        $this->newLine();

        foreach ($results as $locale => $files) {
            $this->line("<fg=cyan>Locale: {$locale}</>");
            
            if (empty($files)) {
                $this->line('  No files processed.');
                continue;
            }

            $successCount = 0;
            $errorCount = 0;
            $skippedCount = 0;

            foreach ($files as $fileName => $result) {
                $status = $result['status'] ?? 'unknown';
                
                switch ($status) {
                    case 'success':
                        $translatedKeys = $result['translated_keys'] ?? 0;
                        $this->line("  <fg=green>✓</> {$fileName} ({$translatedKeys} keys translated)");
                        $successCount++;
                        break;
                    
                    case 'error':
                        $message = $result['message'] ?? 'Unknown error';
                        $this->line("  <fg=red>✗</> {$fileName} - Error: {$message}");
                        $errorCount++;
                        break;
                    
                    case 'skipped':
                        $reason = $result['reason'] ?? 'Unknown reason';
                        $this->line("  <fg=yellow>⚠</> {$fileName} - Skipped: {$reason}");
                        $skippedCount++;
                        break;
                    
                    default:
                        $this->line("  <fg=gray>?</> {$fileName} - Status: {$status}");
                }
            }

            $this->newLine();
            $this->line("  Summary: {$successCount} successful, {$errorCount} errors, {$skippedCount} skipped");
            $this->newLine();
        }

        $this->info('Translation completed!');
    }

    protected function scanSourceFiles(LocaleTranslation $localeTranslation, string $sourceLocale): array
    {
        $reflection = new \ReflectionClass($localeTranslation);
        
        // Get the langPath property
        $langPathProperty = $reflection->getProperty('langPath');
        $langPathProperty->setAccessible(true);
        $langPath = $langPathProperty->getValue($localeTranslation);
        
        $sourceLocalePath = $langPath . DIRECTORY_SEPARATOR . $sourceLocale;
        
        if (!is_dir($sourceLocalePath)) {
            return [];
        }
        
        // Use the scanLangFiles method
        $scanMethod = $reflection->getMethod('scanLangFiles');
        $scanMethod->setAccessible(true);
        
        return $scanMethod->invoke($localeTranslation, $sourceLocalePath);
    }
}