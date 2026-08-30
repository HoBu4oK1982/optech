<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Str;
use DOMDocument;

trait WithRichText
{
    /**
     * Сохраняет base64-картинки из rich-text в /public/upload/{folder}
     * и заменяет src на относительный путь. Логика совместима со старой админкой.
     */
    protected function processInlineImages(?string $html, string $folder): ?string
    {
        if (! $html || ! str_contains($html, 'data:image')) {
            return $html;
        }
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        foreach ($dom->getElementsByTagName('img') as $img) {
            $src = $img->getAttribute('src');
            if (! Str::startsWith($src, 'data:image')) {
                continue;
            }
            [$meta, $content] = explode(';', $src);
            [, $content] = explode(',', $content);
            [, $type] = explode('/', $meta);
            $dir = public_path('/upload/' . $folder);
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $fileName = '/upload/' . $folder . '/' . Str::random(8) . time() . '.' . $type;
            file_put_contents(public_path() . $fileName, base64_decode($content));
            $img->setAttribute('src', $fileName);
        }
        return $dom->saveHTML();
    }
}
