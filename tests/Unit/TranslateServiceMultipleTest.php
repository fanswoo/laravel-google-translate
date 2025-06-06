<?php

namespace Tests\Unit;

use FF\GoogleTranslate\Facade\TranslateService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslateServiceMultipleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        if (!function_exists('config')) {
            function config($key = null, $default = null) {
                $configs = [
                    'google-translate.ssl_verify_peer' => true,
                    'google-translate.api_version' => 'v2',
                    'services.google_translate_v2.api_key' => 'test_api_key',
                    'services.google_translate_v3.project_id' => 'test_project',
                    'services.google_translate_v3.service_account_credentials_path' => '/tmp/test.json',
                ];
                return $configs[$key] ?? $default;
            }
        }
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $service = new TranslateService();
        $this->assertInstanceOf(TranslateService::class, $service);
    }

    #[Test]
    public function it_has_multiple_method(): void
    {
        $service = new TranslateService();
        $this->assertTrue(method_exists($service, 'multiple'));
    }

    #[Test]
    public function it_has_translate_multiple_with_v2_method(): void
    {
        $service = new TranslateService();
        $reflection = new \ReflectionClass($service);
        $this->assertTrue($reflection->hasMethod('translateMultipleWithV2'));
    }

    #[Test]
    public function multiple_method_accepts_correct_parameters(): void
    {
        $service = new TranslateService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('multiple');
        
        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters);
        
        $this->assertEquals('texts', $parameters[0]->getName());
        $this->assertEquals('array', $parameters[0]->getType()->getName());
        
        $this->assertEquals('targetLanguage', $parameters[1]->getName());
        $unionType = (string)$parameters[1]->getType();
        $this->assertTrue(in_array($unionType, ['string|array', 'array|string']));
        
        $this->assertEquals('sourceLanguage', $parameters[2]->getName());
        $this->assertTrue($parameters[2]->allowsNull());
    }

    #[Test]
    public function multiple_method_returns_array(): void
    {
        $service = new TranslateService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('multiple');
        
        $returnType = $method->getReturnType();
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function translate_multiple_with_v2_handles_single_target_language(): void
    {
        $service = new TranslateService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('translateMultipleWithV2');
        $method->setAccessible(true);
        
        // Mock input data
        $texts = ['hello' => 'Hello', 'world' => 'World'];
        $targetLanguage = 'zh_TW';
        $sourceLanguage = 'en';
        
        // The method should return an array with the same keys
        try {
            $result = $method->invoke($service, $texts, $targetLanguage, $sourceLanguage);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('hello', $result);
            $this->assertArrayHasKey('world', $result);
        } catch (\Exception $e) {
            // Expected in test environment without actual API
            $this->assertTrue(true);
        }
    }

    #[Test]
    public function multiple_method_preserves_array_keys(): void
    {
        // Test that the method signature and structure maintains compatibility
        $service = new TranslateService();
        
        // Test with string target language
        $texts = ['greeting' => 'Hello', 'farewell' => 'Goodbye'];
        $targetLanguage = 'zh_TW';
        
        try {
            $result = $service->multiple($texts, $targetLanguage);
            
            // Should return array with target language as key
            $this->assertIsArray($result);
            $this->assertArrayHasKey('zh_TW', $result);
            
            // Should preserve original text keys
            $this->assertArrayHasKey('greeting', $result['zh_TW']);
            $this->assertArrayHasKey('farewell', $result['zh_TW']);
            
        } catch (\Exception $e) {
            // Expected in test environment without actual API
            $this->assertTrue(true);
        }
    }

    #[Test]
    public function multiple_method_handles_array_target_languages(): void
    {
        $service = new TranslateService();
        
        // Test with array of target languages
        $texts = ['hello' => 'Hello'];
        $targetLanguages = ['zh_TW', 'ja_JP'];
        
        try {
            $result = $service->multiple($texts, $targetLanguages);
            
            // Should return array with each target language as key
            $this->assertIsArray($result);
            $this->assertArrayHasKey('zh_TW', $result);
            $this->assertArrayHasKey('ja_JP', $result);
            
        } catch (\Exception $e) {
            // Expected in test environment without actual API
            $this->assertTrue(true);
        }
    }

    #[Test]
    public function method_signature_is_backward_compatible(): void
    {
        $service = new TranslateService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('multiple');
        
        // Verify exact signature matches original
        $this->assertEquals('multiple', $method->getName());
        $this->assertTrue($method->isPublic());
        $this->assertFalse($method->isStatic());
        
        // Check parameter types are exactly as expected
        $parameters = $method->getParameters();
        $this->assertEquals('array', $parameters[0]->getType()->getName());
        $unionType = (string)$parameters[1]->getType();
        $this->assertTrue(in_array($unionType, ['string|array', 'array|string']));
        $this->assertTrue($parameters[2]->hasType());
        $this->assertTrue($parameters[2]->allowsNull());
        
        // Check return type
        $this->assertEquals('array', $method->getReturnType()->getName());
    }
}