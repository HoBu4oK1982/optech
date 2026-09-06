<?php

namespace App\Livewire\Concerns;

use DOMDocument;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait WithRichText
{
    /**
     * Куда складываются картинки, вставленные прямо в текст Summernote.
     * Раньше это была отдельная папка /public/upload/{folder} — теперь общий
     * с обложками каталог /public/assets/images/{folder}.
     */
    protected function inlineImageDir(string $folder): string
    {
        return public_path('assets/images/' . $folder);
    }

    protected function inlineImageUrl(string $folder, string $file): string
    {
        return '/assets/images/' . $folder . '/' . $file;
    }

    /**
     * Имя файла для картинки из текста.
     *
     * Префикс inline_ — не косметика: в /assets/images/{folder} рядом лежат
     * обложки материалов, и без явной пометки чистка «осиротевших» картинок
     * могла бы снести обложку. Удаляем только то, что сами так назвали.
     */
    protected function inlineImageName(string $extension): string
    {
        return 'inline_' . now()->timestamp . Str::random(6) . '.' . ltrim($extension, '.');
    }

    /**
     * Сохраняет base64-картинки из rich-text в /public/assets/images/{folder}
     * и заменяет src на относительный путь.
     *
     * Основной путь загрузки теперь другой — Summernote отправляет файл на
     * /admin/rich-text/image и сразу получает URL (см. RichTextImageController),
     * так что base64 в HTML вообще не попадает. Этот метод остаётся страховкой:
     * картинку всё ещё можно вставить копипастом из буфера обмена, и тогда она
     * приходит как data:image.
     */
    protected function processInlineImages(?string $html, string $folder): ?string
    {
        if (! $html || ! str_contains($html, 'data:image')) {
            return $html;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);

        $dir = $this->inlineImageDir($folder);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        foreach ($dom->getElementsByTagName('img') as $img) {
            $src = $img->getAttribute('src');
            if (! Str::startsWith($src, 'data:image')) {
                continue;
            }

            [$meta, $content] = explode(';', $src);
            [, $content] = explode(',', $content);
            [, $type] = explode('/', $meta);

            $fileName = $this->inlineImageName($type);
            file_put_contents($dir . '/' . $fileName, base64_decode($content));
            $img->setAttribute('src', $this->inlineImageUrl($folder, $fileName));
        }

        return $dom->saveHTML();
    }

    /**
     * Удаляет с диска картинки, которые были в старой версии текста и пропали
     * из новой.
     *
     * Без этого каждая правка статьи оставляла на сервере мусор: файл удалялся
     * из редактора, но продолжал лежать в папке навсегда.
     *
     * @param  array<int,?string>  $oldHtml  все языковые версии ДО сохранения
     * @param  array<int,?string>  $newHtml  все языковые версии ПОСЛЕ сохранения
     */
    protected function deleteOrphanInlineImages(array $oldHtml, array $newHtml, string $folder): int
    {
        $before = $this->collectImageSources($oldHtml);
        $after = $this->collectImageSources($newHtml);

        $removed = 0;

        foreach (array_diff($before, $after) as $src) {
            $path = $this->resolveDeletablePath($src, $folder);

            if ($path && File::exists($path)) {
                File::delete($path);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Пути к картинкам из всех переданных кусков HTML.
     *
     * @param  array<int,?string>  $htmlParts
     * @return array<int,string>
     */
    private function collectImageSources(array $htmlParts): array
    {
        $sources = [];

        foreach ($htmlParts as $html) {
            if (! $html) {
                continue;
            }
            if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m)) {
                foreach ($m[1] as $src) {
                    $sources[] = html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
            }
        }

        return array_values(array_unique($sources));
    }

    /**
     * Абсолютный путь к файлу, который нам разрешено удалить, или null.
     *
     * Удаляем только:
     *   - /upload/{folder}/…            — старая папка, там лежат ТОЛЬКО картинки из текста;
     *   - /assets/images/{folder}/inline_…  — новые картинки из текста.
     * Всё остальное (обложки, og-картинки, внешние ссылки) не трогаем никогда.
     */
    private function resolveDeletablePath(string $src, string $folder): ?string
    {
        // Внешние адреса и data: не наши файлы.
        if (preg_match('#^(https?:)?//#i', $src) || Str::startsWith($src, 'data:')) {
            return null;
        }

        $path = '/' . ltrim(parse_url($src, PHP_URL_PATH) ?: '', '/');
        $name = basename($path);

        // Никаких переходов вверх по дереву.
        if ($name === '' || str_contains($path, '..')) {
            return null;
        }

        $legacyDir = '/upload/' . $folder . '/';
        $currentDir = '/assets/images/' . $folder . '/';

        if ($path === $legacyDir . $name) {
            return public_path(ltrim($path, '/'));
        }

        if ($path === $currentDir . $name && Str::startsWith($name, 'inline_')) {
            return public_path(ltrim($path, '/'));
        }

        return null;
    }

    /**
     * Удаляет файл, заменённый новой загрузкой (обложка, og-картинка).
     * Пустое имя и попытки выйти из каталога игнорируются.
     */
    protected function deleteStoredImage(?string $fileName, string $folder): bool
    {
        if (! $fileName || str_contains($fileName, '/') || str_contains($fileName, '..')) {
            return false;
        }

        $path = public_path('assets/images/' . $folder . '/' . $fileName);

        if (File::exists($path)) {
            File::delete($path);

            return true;
        }

        return false;
    }
}
