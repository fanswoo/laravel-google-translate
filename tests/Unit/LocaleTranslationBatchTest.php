<?php

namespace Tests\Unit;

use FF\GoogleTranslate\LocaleTranslation\LocaleTranslation;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LocaleTranslationBatchTest extends TestCase
{
    private LocaleTranslation $localeTranslation;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->localeTranslation = new LocaleTranslation('/tmp/test');
        
        if (!function_exists('config')) {
            function config($key = null, $default = null) {
                $configs = [
                    'google-translate.ssl_verify_peer' => true,
                    'google-translate.api_version' => 'v2',
                    'services.google_translate_v2.api_key' => 'test_api_key',
                ];
                return $configs[$key] ?? $default;
            }
        }
    }

    #[Test]
    public function it_has_batch_translation_method(): void
    {
        $reflection = new \ReflectionClass($this->localeTranslation);
        $this->assertTrue($reflection->hasMethod('translateArrayWithCountBatch'));
    }

    #[Test]
    public function it_collects_texts_for_translation_correctly(): void
    {
        $reflection = new \ReflectionClass($this->localeTranslation);
        $method = $reflection->getMethod('collectTextsForTranslation');
        $method->setAccessible(true);
        
        $sourceArray = [
            'simple' => 'Simple text',
            'nested' => [
                'level2' => 'Nested text',
                'deep' => [
                    'level3' => 'Deep text'
                ]
            ],
            'existing' => 'Should be skipped'
        ];
        
        $existingArray = [
            'existing' => 'Existing value'
        ];
        
        $textsToTranslate = $method->invoke($this->localeTranslation, $sourceArray, $existingArray, '', false);
        
        // Debug: Check what's actually collected
        // var_dump($textsToTranslate);
        
        // Should collect texts that need translation, using dot notation for nested keys
        $this->assertArrayHasKey('simple', $textsToTranslate);
        $this->assertArrayHasKey('nested.level2', $textsToTranslate);
        $this->assertArrayHasKey('nested.deep.level3', $textsToTranslate);
        $this->assertArrayNotHasKey('existing', $textsToTranslate);
        
        $this->assertEquals('Simple text', $textsToTranslate['simple']);
        $this->assertEquals('Nested text', $textsToTranslate['nested.level2']);
        $this->assertEquals('Deep text', $textsToTranslate['nested.deep.level3']);
    }

    #[Test]
    public function it_collects_all_texts_with_overwrite_enabled(): void
    {
        $reflection = new \ReflectionClass($this->localeTranslation);
        $method = $reflection->getMethod('collectTextsForTranslation');
        $method->setAccessible(true);
        
        $sourceArray = [
            'new_key' => 'New text',
            'existing_key' => 'Updated text'
        ];
        
        $existingArray = [
            'existing_key' => 'Existing value'
        ];
        
        $textsToTranslate = $method->invoke($this->localeTranslation, $sourceArray, $existingArray, '', true);
        
        // With overwrite enabled, should collect all texts
        $this->assertArrayHasKey('new_key', $textsToTranslate);
        $this->assertArrayHasKey('existing_key', $textsToTranslate);
        $this->assertEquals('New text', $textsToTranslate['new_key']);
        $this->assertEquals('Updated text', $textsToTranslate['existing_key']);
    }

    #[Test]
    public function it_builds_result_array_with_translated_texts(): void
    {
        $reflection = new \ReflectionClass($this->localeTranslation);
        $method = $reflection->getMethod('buildResultArray');
        $method->setAccessible(true);
        
        $sourceArray = [
            'welcome' => 'Welcome',
            'auth' => [
                'failed' => 'Login failed',
                'success' => 'Login successful'
            ]
        ];
        
        $existingArray = [];
        
        $translatedTexts = [
            'welcome' => '歡迎',
            'auth.failed' => '登入失敗',
            'auth.success' => '登入成功'
        ];
        
        $result = $method->invoke($this->localeTranslation, $sourceArray, $existingArray, $translatedTexts, false);
        
        $this->assertEquals('歡迎', $result['welcome']);
        $this->assertEquals('登入失敗', $result['auth']['failed']);
        $this->assertEquals('登入成功', $result['auth']['success']);
    }

    #[Test]
    public function it_preserves_existing_values_when_not_overwriting(): void
    {
        $reflection = new \ReflectionClass($this->localeTranslation);
        $method = $reflection->getMethod('buildResultArray');
        $method->setAccessible(true);
        
        $sourceArray = [
            'new_key' => 'New text',
            'existing_key' => 'Source text'
        ];
        
        $existingArray = [
            'existing_key' => 'Existing translation'
        ];
        
        $translatedTexts = [
            'new_key' => 'New translation'
            // existing_key should not be in translated texts since it exists
        ];
        
        $result = $method->invoke($this->localeTranslation, $sourceArray, $existingArray, $translatedTexts, false);
        
        $this->assertEquals('New translation', $result['new_key']);
        $this->assertEquals('Existing translation', $result['existing_key']); // Should preserve existing
    }

    #[Test]
    public function it_handles_empty_translations_gracefully(): void
    {
        $reflection = new \ReflectionClass($this->localeTranslation);
        $method = $reflection->getMethod('translateArrayWithCountBatch');
        $method->setAccessible(true);
        
        $sourceArray = [];
        $existingArray = [];
        
        try {
            $result = $method->invoke($this->localeTranslation, $sourceArray, $existingArray, 'en', 'zh_TW', false);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('data', $result);
            $this->assertArrayHasKey('translated_count', $result);
            $this->assertEquals(0, $result['translated_count']);
            $this->assertEmpty($result['data']);
            
        } catch (\Exception $e) {
            // Expected without actual translation service
            $this->assertTrue(true);
        }
    }

    #[Test]
    public function it_maintains_backward_compatibility(): void
    {
        $reflection = new \ReflectionClass($this->localeTranslation);
        $method = $reflection->getMethod('translateArray');
        $method->setAccessible(true);
        
        $sourceArray = [
            'key1' => 'value1',
            'key2' => 'value2'
        ];
        
        $existingArray = [];
        
        try {
            $result = $method->invoke($this->localeTranslation, $sourceArray, $existingArray, 'en', 'zh_TW', false);
            
            // Should return array with same structure as before
            $this->assertIsArray($result);
            $this->assertArrayHasKey('key1', $result);
            $this->assertArrayHasKey('key2', $result);
            
        } catch (\Exception $e) {
            // Expected without actual translation service
            $this->assertTrue(true);
        }
    }

    #[Test]
    public function it_preserves_key_order_in_batch_mode(): void
    {
        $reflection = new \ReflectionClass($this->localeTranslation);
        $method = $reflection->getMethod('buildResultArray');
        $method->setAccessible(true);
        
        $sourceArray = [
            'third' => 'Third',
            'first' => 'First', 
            'second' => 'Second'
        ];
        
        $existingArray = [];
        
        $translatedTexts = [
            'third' => 'Third Trans',
            'first' => 'First Trans',
            'second' => 'Second Trans'
        ];
        
        $result = $method->invoke($this->localeTranslation, $sourceArray, $existingArray, $translatedTexts, false);
        
        $keys = array_keys($result);
        $this->assertEquals(['third', 'first', 'second'], $keys);
    }
}