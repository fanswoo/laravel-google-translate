<?php

namespace Tests\Unit;

use FF\GoogleTranslate\GoogleTranslateClient\GoogleTranslateClient3;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\FetchAuthTokenInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GoogleTranslateClient3CredentialsTest extends TestCase
{
    private string $directory;

    private string|false $applicationCredentials;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/google-translate-credentials-'.uniqid();
        mkdir($this->directory);

        $this->applicationCredentials = getenv('GOOGLE_APPLICATION_CREDENTIALS');
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.$this->writeKeyFile('application-default@test.iam.gserviceaccount.com'));
    }

    protected function tearDown(): void
    {
        putenv($this->applicationCredentials === false
            ? 'GOOGLE_APPLICATION_CREDENTIALS'
            : 'GOOGLE_APPLICATION_CREDENTIALS='.$this->applicationCredentials);

        array_map('unlink', glob($this->directory.'/*.json'));
        rmdir($this->directory);

        parent::tearDown();
    }

    #[Test]
    public function usesTheApplicationDefaultCredentialsWithoutAKeyFile(): void
    {
        $client = new CredentialsExposingClient;
        $client->setServiceAccountCredentialsPath(null);

        $this->assertSame('application-default@test.iam.gserviceaccount.com', $this->clientEmail($client));
    }

    #[Test]
    public function usesTheApplicationDefaultCredentialsForAnEmptyKeyFilePath(): void
    {
        $client = new CredentialsExposingClient;
        $client->setServiceAccountCredentialsPath('');

        $this->assertSame('application-default@test.iam.gserviceaccount.com', $this->clientEmail($client));
    }

    #[Test]
    public function usesTheKeyFileWhenOneIsSet(): void
    {
        $client = new CredentialsExposingClient;
        $client->setServiceAccountCredentialsPath($this->writeKeyFile('key-file@test.iam.gserviceaccount.com'));

        $this->assertSame('key-file@test.iam.gserviceaccount.com', $this->clientEmail($client));
    }

    #[Test]
    public function rejectsAKeyFilePathThatDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new CredentialsExposingClient)->setServiceAccountCredentialsPath($this->directory.'/missing.json');
    }

    private function clientEmail(CredentialsExposingClient $client): string
    {
        $credentials = $client->exposedCredentials(['https://www.googleapis.com/auth/cloud-translation']);

        $this->assertInstanceOf(ServiceAccountCredentials::class, $credentials);

        return $credentials->getClientName();
    }

    private function writeKeyFile(string $clientEmail): string
    {
        $path = $this->directory.'/'.md5($clientEmail).'.json';

        file_put_contents($path, json_encode([
            'type' => 'service_account',
            'project_id' => 'test',
            'private_key_id' => 'test',
            'private_key' => 'test',
            'client_email' => $clientEmail,
            'client_id' => 'test',
        ]));

        return $path;
    }
}

/**
 * Exposes the credentials that the client signs its requests with.
 */
class CredentialsExposingClient extends GoogleTranslateClient3
{
    /**
     * @param  array<int, string>  $scopes
     */
    public function exposedCredentials(array $scopes): FetchAuthTokenInterface
    {
        return $this->credentials($scopes);
    }
}
