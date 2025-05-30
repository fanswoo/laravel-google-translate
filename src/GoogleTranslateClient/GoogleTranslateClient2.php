<?php

namespace FF\GoogleTranslate\GoogleTranslateClient;

class GoogleTranslateClient2
{
    protected string $apiKey;

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function translate(string $text, string $targetLanguage, ?string $sourceLanguage = null)
    {
        $apiUrl = 'https://translation.googleapis.com/language/translate/v2';

        $data = [
            'q' => $text,
            'source' => $sourceLanguage,
            'target' => $targetLanguage,
            'format' => 'text'
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiUrl . '?key=' . $this->apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        if (config('google-translate.ssl_verify_peer') === false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return [
                'httpCode' => $httpCode,
                'error' => $error,
            ];
        }
        curl_close($ch);

        if ($httpCode !== 200) {
            return [
                'httpCode' => $httpCode,
                'response' => $response,
            ];
        }

        $responseData = json_decode($response, true);

        if (isset($responseData['error'])) {
            return $responseData;
        } elseif (isset($responseData['data']['translations'][0]['translatedText'])) {
            return $responseData['data']['translations'][0]['translatedText'];
        }
        return $responseData;
    }
}