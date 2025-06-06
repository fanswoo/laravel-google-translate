<?php

namespace Tests\Unit;

use FF\GoogleTranslate\Console\Commands\TranslateLangCommand;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslateLangCommandTest extends TestCase
{
    private string $testLangPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testLangPath = sys_get_temp_dir() . '/test_command_lang_' . uniqid();
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
        
        file_put_contents($this->testLangPath . '/en/messages.php', "<?php\n\nreturn [\n    'welcome' => 'Welcome',\n    'goodbye' => 'Goodbye'\n];");
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $command = new TranslateLangCommand();
        $this->assertInstanceOf(TranslateLangCommand::class, $command);
    }

    #[Test]
    public function it_has_correct_signature(): void
    {
        $command = new TranslateLangCommand();
        $this->assertEquals('translate:lang', $command->getName());
    }

    #[Test]
    public function it_has_description(): void
    {
        $command = new TranslateLangCommand();
        $this->assertStringContainsString('Translate language files', $command->getDescription());
    }

    #[Test]
    public function it_has_correct_signature_components(): void
    {
        $command = new TranslateLangCommand();
        $definition = $command->getDefinition();
        
        // Check required arguments
        $this->assertTrue($definition->hasArgument('source'));
        $this->assertTrue($definition->hasArgument('targets'));
        
        // Check options
        $this->assertTrue($definition->hasOption('overwrite'));
        $this->assertTrue($definition->hasOption('path'));
    }

    #[Test]
    public function it_has_required_argument_properties(): void
    {
        $command = new TranslateLangCommand();
        $definition = $command->getDefinition();
        
        $sourceArg = $definition->getArgument('source');
        $this->assertTrue($sourceArg->isRequired());
        
        $targetsArg = $definition->getArgument('targets');
        $this->assertTrue($targetsArg->isArray());
    }
}