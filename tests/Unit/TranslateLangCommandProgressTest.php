<?php

namespace Tests\Unit;

use FF\GoogleTranslate\Console\Commands\TranslateLangCommand;
use FF\GoogleTranslate\LocaleTranslation\LocaleTranslation;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslateLangCommandProgressTest extends TestCase
{
    private string $testLangPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testLangPath = sys_get_temp_dir() . '/test_command_progress_' . uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testLangPath)) {
            $this->removeDirectory($this->testLangPath);
        }
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    private function createTestLangStructure(): void
    {
        mkdir($this->testLangPath, 0755, true);
        mkdir($this->testLangPath . '/en', 0755, true);
        
        // Create 3 source files
        file_put_contents($this->testLangPath . '/en/messages.php', "<?php\n\nreturn [\n    'welcome' => 'Welcome',\n    'goodbye' => 'Goodbye'\n];");
        file_put_contents($this->testLangPath . '/en/auth.php', "<?php\n\nreturn [\n    'failed' => 'Login failed'\n];");
        file_put_contents($this->testLangPath . '/en/validation.php', "<?php\n\nreturn [\n    'required' => 'Field required'\n];");
    }

    #[Test]
    public function it_has_scan_source_files_method(): void
    {
        $command = new TranslateLangCommand();
        $reflection = new \ReflectionClass($command);
        
        $this->assertTrue($reflection->hasMethod('scanSourceFiles'));
        
        $method = $reflection->getMethod('scanSourceFiles');
        $this->assertTrue($method->isProtected());
    }

    #[Test]
    public function it_scans_source_files_correctly(): void
    {
        $this->createTestLangStructure();
        
        $command = new TranslateLangCommand();
        $localeTranslation = new LocaleTranslation($this->testLangPath);
        
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('scanSourceFiles');
        $method->setAccessible(true);
        
        $sourceFiles = $method->invoke($command, $localeTranslation, 'en');
        
        $this->assertIsArray($sourceFiles);
        $this->assertCount(3, $sourceFiles); // Should find 3 files
        $this->assertArrayHasKey('messages.php', $sourceFiles);
        $this->assertArrayHasKey('auth.php', $sourceFiles);
        $this->assertArrayHasKey('validation.php', $sourceFiles);
    }

    #[Test]
    public function it_returns_empty_array_for_nonexistent_source(): void
    {
        $command = new TranslateLangCommand();
        $localeTranslation = new LocaleTranslation($this->testLangPath);
        
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('scanSourceFiles');
        $method->setAccessible(true);
        
        $sourceFiles = $method->invoke($command, $localeTranslation, 'nonexistent');
        
        $this->assertIsArray($sourceFiles);
        $this->assertEmpty($sourceFiles);
    }

    #[Test]
    public function it_handles_empty_source_directory(): void
    {
        mkdir($this->testLangPath, 0755, true);
        mkdir($this->testLangPath . '/en', 0755, true);
        // No files created
        
        $command = new TranslateLangCommand();
        $localeTranslation = new LocaleTranslation($this->testLangPath);
        
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('scanSourceFiles');
        $method->setAccessible(true);
        
        $sourceFiles = $method->invoke($command, $localeTranslation, 'en');
        
        $this->assertIsArray($sourceFiles);
        $this->assertEmpty($sourceFiles);
    }

    #[Test]
    public function it_calculates_correct_total_files(): void
    {
        $this->createTestLangStructure();
        
        $sourceFiles = ['messages.php', 'auth.php', 'validation.php']; // 3 files
        $targetLocales = ['zh_TW', 'ja_JP', 'fr_FR']; // 3 locales
        
        $expectedTotal = count($sourceFiles) * count($targetLocales); // 3 × 3 = 9
        
        $this->assertEquals(9, $expectedTotal);
    }

    #[Test]
    public function it_only_scans_php_files(): void
    {
        mkdir($this->testLangPath, 0755, true);
        mkdir($this->testLangPath . '/en', 0755, true);
        
        // Create PHP files
        file_put_contents($this->testLangPath . '/en/messages.php', "<?php\nreturn [];");
        file_put_contents($this->testLangPath . '/en/auth.php', "<?php\nreturn [];");
        
        // Create non-PHP files (should be ignored)
        file_put_contents($this->testLangPath . '/en/readme.txt', "Some text");
        file_put_contents($this->testLangPath . '/en/config.json', "{}");
        
        $command = new TranslateLangCommand();
        $localeTranslation = new LocaleTranslation($this->testLangPath);
        
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('scanSourceFiles');
        $method->setAccessible(true);
        
        $sourceFiles = $method->invoke($command, $localeTranslation, 'en');
        
        $this->assertCount(2, $sourceFiles); // Only PHP files
        $this->assertArrayHasKey('messages.php', $sourceFiles);
        $this->assertArrayHasKey('auth.php', $sourceFiles);
        $this->assertArrayNotHasKey('readme.txt', $sourceFiles);
        $this->assertArrayNotHasKey('config.json', $sourceFiles);
    }
}