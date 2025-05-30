<?php

namespace FF\GoogleTranslate\GoogleTranslateClient;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

class GoogleTranslateClient3
{

    protected string $projectId;

    protected string $serviceAccountKeyFilePath;

    protected array $errorMessage = [];

    public function setProjectId(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    public function setServiceAccountCredentialsPath(string $path): void
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("服務帳戶金鑰檔案不存在: {$path}");
        }
        $this->serviceAccountKeyFilePath = $path;
    }

    protected function getAccessToken(): ?string
    {
        try {
            $scopes = ['https://www.googleapis.com/auth/cloud-translation'];

            $credentials = new ServiceAccountCredentials($scopes, $this->serviceAccountKeyFilePath);

            $tokenData = $credentials->fetchAuthToken(HttpHandlerFactory::build());
            if (isset($tokenData['access_token'])) {
                $accessToken = $tokenData['access_token'];
            } else {
                throw new \Exception("未能從憑證中獲取 access_token。");
            }
        } catch (\Exception $e) {
            die('獲取 Access Token 時發生錯誤: ' . $e->getMessage());
        }

        return $accessToken;
    }

    public function translate(array $texts, string $targetLanguage, ?string $sourceLanguage = null)
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            die('無法獲取 Access Token，請檢查服務帳戶設定和金鑰檔案路徑。');
        }

        $apiUrl = 'https://translation.googleapis.com/v3/projects/' . $this->projectId . ':translateText';

        $data = [
            'contents' => $texts,
            'targetLanguageCode' => $targetLanguage,
            'sourceLanguageCode' => $sourceLanguage,
            'mimeType' => 'text/plain'
        ];
        if (empty($sourceLanguage)) {
            unset($data['sourceLanguageCode']);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        if (config('google-translate.ssl_verify_peer') === false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            $this->errorMessage = [
                'httpCode' => $httpCode,
                'error' => $error,
            ];
            return false;
        }
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->errorMessage = [
                'httpCode' => $httpCode,
                'response' => $response,
            ];
            return false;
        }

        $responseData = json_decode($response, true);

        if (isset($responseData['error'])) {
            $this->errorMessage = $responseData;
            return false;
        } elseif (isset($responseData['translations'])) {
            return $responseData['translations'];
        }
        $this->errorMessage = $responseData;
        return false;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}