<?php

namespace Tests\Unit;

use FF\GoogleTranslate\LocaleTranslation\LocaleTranslation;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LocaleTranslationIntegrationTest extends TestCase
{
    private string $testLangPath;
    private LocaleTranslation $localeTranslation;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testLangPath = sys_get_temp_dir() . '/test_lang_integration_' . uniqid();
        $this->localeTranslation = new LocaleTranslation($this->testLangPath);
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

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $localeTranslation = new LocaleTranslation();
        $this->assertInstanceOf(LocaleTranslation::class, $localeTranslation);
    }

    #[Test]
    public function it_can_be_instantiated_with_custom_path(): void
    {
        $customPath = '/custom/path';
        $localeTranslation = new LocaleTranslation($customPath);
        $this->assertInstanceOf(LocaleTranslation::class, $localeTranslation);
    }

    #[Test]
    public function it_validates_lang_directory_exists(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Lang directory does not exist:');
        
        $this->localeTranslation->translate('en', ['zh_TW'], false);
    }

    #[Test]
    public function it_validates_source_locale_directory_exists(): void
    {
        mkdir($this->testLangPath, 0755, true);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source locale directory does not exist:');
        
        $this->localeTranslation->translate('nonexistent', ['zh_TW'], false);
    }

    #[Test]
    public function it_can_scan_empty_source_directory(): void
    {
        mkdir($this->testLangPath, 0755, true);
        mkdir($this->testLangPath . '/en', 0755, true);
        
        $reflection = new \ReflectionClass($this->localeTranslation);
        $method = $reflection->getMethod('scanLangFiles');
        $method->setAccessible(true);
        
        $files = $method->invoke($this->localeTranslation, $this->testLangPath . '/en');
        
        $this->assertIsArray($files);
        $this->assertEmpty($files);
    }

    #[Test]
    public function it_handles_array_to_php_string_conversion(): void
    {
        $reflection = new \ReflectionClass($this->localeTranslation);
        $method = $reflection->getMethod('arrayToPhpString');
        $method->setAccessible(true);
        
        $simpleArray = ['key' => 'value'];
        $result = $method->invoke($this->localeTranslation, $simpleArray);
        
        $this->assertStringContainsString("'key' => 'value'", $result);
        $this->assertStringStartsWith('[', $result);
        $this->assertStringEndsWith(']', $result);
    }

    #[Test]
    public function it_escapes_quotes_in_array_values(): void
    {
        $reflection = new \ReflectionClass($this->localeTranslation);
        $method = $reflection->getMethod('arrayToPhpString');
        $method->setAccessible(true);
        
        $arrayWithQuotes = ['message' => "Don't forget"];
        $result = $method->invoke($this->localeTranslation, $arrayWithQuotes);
        
        $this->assertStringContainsString("Don\\'t forget", $result);
    }

    #[Test]
    public function it_handles_nested_array_conversion(): void
    {
        $reflection = new \ReflectionClass($this->localeTranslation);
        $method = $reflection->getMethod('arrayToPhpString');
        $method->setAccessible(true);
        
        $nestedArray = [
            'level1' => [
                'level2' => 'value'
            ]
        ];
        
        $result = $method->invoke($this->localeTranslation, $nestedArray);
        
        $this->assertStringContainsString("'level1' => [", $result);
        $this->assertStringContainsString("'level2' => 'value'", $result);
    }

    #[Test]
    public function it_flattens_complex_nested_arrays(): void
    {
        $reflection = new \ReflectionClass($this->localeTranslation);
        $method = $reflection->getMethod('flattenArray');
        $method->setAccessible(true);
        
        $complexArray = [
            'auth' => [
                'failed' => 'Authentication failed',
                'password' => [
                    'incorrect' => 'Password is incorrect',
                    'weak' => 'Password is too weak'
                ]
            ],
            'validation' => [
                'required' => 'This field is required'
            ]
        ];
        
        $result = $method->invoke($this->localeTranslation, $complexArray);
        
        $expected = [
            'auth.failed' => 'Authentication failed',
            'auth.password.incorrect' => 'Password is incorrect',
            'auth.password.weak' => 'Password is too weak',
            'validation.required' => 'This field is required'
        ];
        
        $this->assertEquals($expected, $result);
    }
}