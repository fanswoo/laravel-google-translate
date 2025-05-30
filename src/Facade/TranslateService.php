<?php

namespace FF\GoogleTranslate\Facade;

use FF\GoogleTranslate\GoogleTranslateClient\GoogleTranslateClient2;
use FF\GoogleTranslate\GoogleTranslateClient\GoogleTranslateClient3;

class TranslateService
{
    public function get(string $text, string $targetLanguage, ?string $sourceLanguage = null, ?string $apiVersion = null): string
    {
        $apiVersion = $apiVersion ?? config('google-translate.api_version', 'v2');
        if($apiVersion === 'v2')
        {
            $client = new GoogleTranslateClient2;
            $client->setApiKey(config('services.google_translate_v2.api_key'));
            return $client->translate($text, $targetLanguage, $sourceLanguage);
        }
        else
        {
            $client = new GoogleTranslateClient3;
            $client->setProjectId(config('services.google_translate_v3.project_id'));
            $client->setServiceAccountCredentialsPath(config('services.google_translate_v3.service_account_credentials_path'));
            return $client->translate([$text], $targetLanguage, $sourceLanguage)[0]['translatedText'] ?? '';
        }
    }

    public function multiple(array $texts, string|array $targetLanguage, ?string $sourceLanguage = null): array
    {
        $targetLanguages = is_array($targetLanguage) ? $targetLanguage : [$targetLanguage];
        $translates = [];
        foreach($targetLanguages as $targetLanguage)
        {
            $client = new GoogleTranslateClient3;
            $client->setProjectId(config('services.google_translate_v3.project_id'));
            $client->setServiceAccountCredentialsPath(config('services.google_translate_v3.service_account_credentials_path'));
            $results = $client->translate(array_values($texts), $targetLanguage, $sourceLanguage);
            if($results === false)
            {
                return $client->getErrorMessage();
            }
            $textKeys = array_keys($texts);

            foreach ($results as $key => $result) {
                $textKey = $textKeys[$key];
                $translates[$targetLanguage][$textKey] = $result['translatedText'] ?? '';
            }
        }
        return $translates;
    }
}