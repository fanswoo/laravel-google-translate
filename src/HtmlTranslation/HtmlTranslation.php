<?php

namespace FF\GoogleTranslate\HtmlTranslation;

use FF\GoogleTranslate\Facade\Translate;

class HtmlTranslation
{
    public function translate(string $html, string $targetLanguage, ?string $sourceLanguage = null): string
    {
        if (empty(trim($html))) {
            return $html;
        }

        // 首先嘗試使用 HTMLDocument，如果不可用則使用 DOMDocument
        $dom = $this->createDomDocument($html);
        
        // 取得所有文字節點
        $textNodes = $this->getTextNodes($dom);
        
        if (empty($textNodes)) {
            return $html;
        }
        
        // 準備要翻譯的文字
        $textsToTranslate = [];
        foreach ($textNodes as $index => $node) {
            $text = trim($node->textContent);
            if (!empty($text)) {
                $textsToTranslate[$index] = $text;
            }
        }
        
        if (empty($textsToTranslate)) {
            return $html;
        }

        // 直接使用 Translate::multiple() 翻譯文字
        $translatedTexts = Translate::multiple($textsToTranslate, $targetLanguage, $sourceLanguage);
        $targetTranslations = $translatedTexts[$targetLanguage] ?? [];
        
        // 將翻譯後的文字設定回節點
        foreach ($targetTranslations as $index => $translatedText) {
            if (isset($textNodes[$index]) && !empty($translatedText)) {
                $textNodes[$index]->textContent = $translatedText;
            }
        }
        
        return $this->extractHtmlFromDom($dom, $html);
    }

    private function createDomDocument(string $html)
    {
        // 使用 DOMDocument 作為備用方案
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        return $dom;
    }

    private function getTextNodes($dom): array
    {
        $textNodes = [];
        $xpath = new \DOMXPath($dom);
        
        // 查詢所有包含文字的節點，排除 script 和 style 標籤
        $nodeList = $xpath->query('//text()[normalize-space(.) != "" and not(ancestor::script) and not(ancestor::style)]');
        
        foreach ($nodeList as $node) {
            $textNodes[] = $node;
        }
        
        return $textNodes;
    }

    private function extractHtmlFromDom($dom, string $originalHtml): string
    {
        // 對於 DOMDocument，需要提取 body 內容
        $body = $dom->getElementsByTagName('body')->item(0);
        $output = '';
        if ($body) {
            foreach ($body->childNodes as $node) {
                $output .= $dom->saveHTML($node);
            }
        }
        return $output;
    }
}