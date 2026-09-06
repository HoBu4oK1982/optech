<?php

namespace App\Livewire\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Общий набор SEO-полей для Livewire-форм админки.
 *
 * Свойства здесь названы ровно так, как их ждёт переиспользуемый блок
 * resources/views/admin/partials/seo.blade.php, — поэтому компоненту
 * достаточно подключить трейт, а шаблону сделать @include('admin.partials.seo').
 *
 * Использование:
 *   use HasSeoFields;
 *   // в mount():   $this->loadSeoFields($model);
 *   // при сохранении: $this->applySeoFields($model, 'brands');
 */
trait HasSeoFields
{
    // Meta по языкам
    public $meta_title, $meta_title_en, $meta_title_kz;
    public $meta_description, $meta_description_en, $meta_description_kz;
    public $meta_keywords, $meta_keywords_en, $meta_keywords_kz;

    // Заголовки
    public $seo_h1, $seo_h1_en, $seo_h1_kz, $seo_h2;

    // Open Graph
    public $og_title, $og_description;
    /** Новый загружаемый файл (Livewire TemporaryUploadedFile). */
    public $og_image;
    /** Имя уже сохранённого файла — для превью в форме. */
    public $existing_og_image;

    // Индексация
    public $canonical_url;
    public $is_indexable = true;
    public $is_followable = true;

    // Изображения
    public $image_alt, $image_alt_en, $image_alt_kz, $image_title;

    // Sitemap
    public $in_sitemap = true;
    public $sitemap_priority = 0.6;
    public $sitemap_changefreq = 'weekly';

    /**
     * Поля, которые есть только у части сущностей (например, SEO-тексты и
     * breadcrumb_title у брендов). Компонент переопределяет метод и добавляет
     * свои колонки — они начинают читаться и сохраняться наравне с общими.
     */
    protected function extraSeoFields(): array
    {
        return [];
    }

    /** Простые (не файловые) SEO-поля — общий список для чтения и записи. */
    protected function seoScalarFields(): array
    {
        return array_merge([
            'meta_title', 'meta_title_en', 'meta_title_kz',
            'meta_description', 'meta_description_en', 'meta_description_kz',
            'meta_keywords', 'meta_keywords_en', 'meta_keywords_kz',
            'seo_h1', 'seo_h1_en', 'seo_h1_kz', 'seo_h2',
            'og_title', 'og_description',
            'canonical_url',
            'image_alt', 'image_alt_en', 'image_alt_kz', 'image_title',
            'sitemap_changefreq',
        ], $this->extraSeoFields());
    }

    /** Заполняет свойства формы из модели. */
    protected function loadSeoFields(Model $model): void
    {
        foreach ($this->seoScalarFields() as $field) {
            $this->{$field} = $model->{$field};
        }

        $this->existing_og_image = $model->og_image;

        // В базе поля с default true, но у записей, созданных до миграции,
        // значение может прийти null — трактуем его как «разрешено».
        $this->is_indexable = $model->is_indexable === null ? true : (bool) $model->is_indexable;
        $this->is_followable = $model->is_followable === null ? true : (bool) $model->is_followable;
        $this->in_sitemap = $model->in_sitemap === null ? true : (bool) $model->in_sitemap;
        $this->sitemap_priority = $model->sitemap_priority ?? 0.6;
    }

    /**
     * Переносит свойства формы в модель. Модель не сохраняется — вызывающий
     * код сам решает, когда делать save().
     *
     * $imageFolder — папка в storage для OG-картинки (brands / services / ...).
     */
    protected function applySeoFields(Model $model, string $imageFolder): void
    {
        foreach ($this->seoScalarFields() as $field) {
            $model->{$field} = $this->{$field};
        }

        $model->is_indexable = (bool) $this->is_indexable;
        $model->is_followable = (bool) $this->is_followable;
        $model->in_sitemap = (bool) $this->in_sitemap;

        // Пустая строка из формы не должна уехать в decimal-колонку.
        $priority = is_numeric($this->sitemap_priority) ? (float) $this->sitemap_priority : 0.6;
        $model->sitemap_priority = max(0, min(1, $priority));

        if ($this->og_image) {
            $name = 'og_' . Carbon::now()->timestamp . '.' . $this->og_image->extension();
            $this->og_image->storeAs($imageFolder, $name);
            $model->og_image = $name;
            $this->existing_og_image = $name;
            $this->og_image = null;
        }
    }
}
