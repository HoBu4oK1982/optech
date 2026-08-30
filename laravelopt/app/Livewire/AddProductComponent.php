<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductTranslate;
use App\Livewire\Concerns\WithRepeaters;
use DOMDocument;

class AddProductComponent extends Component
{
    use WithFileUploads, WithRepeaters;

    // --- Основное ---
    public $name, $slug, $SKU, $category_id, $brand_id, $status = 0, $sort_order = 0;
    public $description, $char, $usage, $short_description;

    // --- Переводы EN/KZ ---
    public $name_en, $name_kz, $description_en, $description_kz;
    public $usage_en, $usage_kz, $char_en, $char_kz;
    public $short_description_en, $short_description_kz;

    // --- Коммерция ---
    public $price, $old_price, $currency = 'KZT', $price_on_request = false;
    public $availability = 'InStock', $gtin, $mpn;

    // --- Медиа ---
    public $image;                 // основное фото (upload)
    public $galleryUploads = [];   // новые загрузки галереи
    public array $gallery = [];    // уже сохранённые имена файлов
    public $documentUploads = [];  // новые загрузки документов
    public array $documents = [];  // [{name, file}]
    public $og_image;              // og image (upload)
    public $video_url;

    // --- FAQ ---
    public array $faq = [];

    // --- SEO (RU) ---
    public $meta_title, $meta_description, $meta_keywords, $seo_h1;
    // --- SEO (EN/KZ) ---
    public $meta_title_en, $meta_title_kz, $meta_description_en, $meta_description_kz;
    public $meta_keywords_en, $meta_keywords_kz, $seo_h1_en, $seo_h1_kz;
    public $canonical_url, $is_indexable = true;
    // ТЗ: OG, follow, alt/title, H2, sitemap
    public $og_title, $og_description, $seo_h2;
    public $image_alt, $image_alt_en, $image_alt_kz, $image_title;
    public $is_followable = true, $in_sitemap = true, $sitemap_priority = 0.5, $sitemap_changefreq = 'weekly';

    protected $rules = [
        'name'  => 'required|string|max:255',
        'slug'  => 'required|unique:products,slug',
        'price' => 'nullable|numeric|min:0',
        'old_price' => 'nullable|numeric|min:0',
        'image' => 'nullable|image|max:5120',
        'og_image' => 'nullable|image|max:5120',
        'galleryUploads.*' => 'nullable|image|max:5120',
    ];

    protected $messages = [
        'slug.unique'  => 'Такой URL (slug) уже существует!',
        'name.required'=> 'Укажите название товара.',
    ];

    public function updated($prop)
    {
        $this->validateOnly($prop);
    }

    public function generateSlug()
    {
        $this->slug = Str::slug($this->name, '-');
    }

    public function removeGallery($index)
    {
        unset($this->gallery[$index]);
        $this->gallery = array_values($this->gallery);
    }

    public function removeDocument($index)
    {
        unset($this->documents[$index]);
        $this->documents = array_values($this->documents);
    }

    /** Сохранить base64-картинки из rich-text в /upload/products и заменить src. */
    private function processInlineImages(?string $html): ?string
    {
        if (! $html || ! str_contains($html, 'data:image')) {
            return $html;
        }
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if (! Str::startsWith($src, 'data:image')) {
                continue;
            }
            [$meta, $content] = explode(';', $src);
            [, $content] = explode(',', $content);
            [, $type] = explode('/', $meta);
            $fileName = '/upload/products/' . Str::random(8) . time() . '.' . $type;
            if (! is_dir(public_path('/upload/products'))) {
                @mkdir(public_path('/upload/products'), 0775, true);
            }
            file_put_contents(public_path() . $fileName, base64_decode($content));
            $img->setAttribute('src', $fileName);
        }
        return $dom->saveHTML();
    }

    public function addProduct()
    {
        $this->validate();

        $product = new Product();
        $product->name = $this->name;
        $product->slug = $this->slug;
        $product->SKU = $this->SKU;
        $product->category_id = $this->category_id ?: 0;
        $product->brand_id = $this->brand_id;
        $product->status = $this->status ?: 0;
        $product->sort_order = (int) $this->sort_order;

        $product->description = $this->processInlineImages($this->description);
        $product->char = $this->char;
        $product->usage = $this->usage;
        $product->short_description = $this->short_description;

        // Коммерция
        $product->price = $this->price_on_request ? null : $this->price;
        $product->old_price = $this->old_price;
        $product->currency = $this->currency ?: 'KZT';
        $product->price_on_request = (bool) $this->price_on_request;
        $product->availability = $this->availability ?: 'InStock';
        $product->gtin = $this->gtin;
        $product->mpn = $this->mpn;
        $product->video_url = $this->video_url;
        $product->faq = array_values(array_filter($this->faq, fn ($f) => ! empty($f['question'])));

        // SEO
        $product->meta_title = $this->meta_title;
        $product->meta_description = $this->meta_description;
        $product->meta_keywords = $this->meta_keywords;
        $product->seo_h1 = $this->seo_h1;
        $product->canonical_url = $this->canonical_url;
        $product->is_indexable = (bool) $this->is_indexable;
        $product->is_followable = (bool) $this->is_followable;
        $product->og_title = $this->og_title;
        $product->og_description = $this->og_description;
        $product->seo_h2 = $this->seo_h2;
        $product->image_alt = $this->image_alt;
        $product->image_alt_en = $this->image_alt_en;
        $product->image_alt_kz = $this->image_alt_kz;
        $product->image_title = $this->image_title;
        $product->in_sitemap = (bool) $this->in_sitemap;
        $product->sitemap_priority = $this->sitemap_priority;
        $product->sitemap_changefreq = $this->sitemap_changefreq;

        // Основное изображение
        if ($this->image) {
            $imageName = Carbon::now()->timestamp . '_' . Str::random(5) . '.' . $this->image->extension();
            $this->image->storeAs('products', $imageName);
            $product->image = $imageName;
        }
        // OG image
        if ($this->og_image) {
            $ogName = 'og_' . Carbon::now()->timestamp . '.' . $this->og_image->extension();
            $this->og_image->storeAs('products', $ogName);
            $product->og_image = $ogName;
        }
        // Галерея
        $gallery = $this->gallery;
        foreach ($this->galleryUploads as $file) {
            $gName = 'g_' . Str::random(8) . '.' . $file->extension();
            $file->storeAs('products', $gName);
            $gallery[] = $gName;
        }
        $product->gallery = array_values($gallery);

        // Документы
        $docs = $this->documents;
        foreach ($this->documentUploads as $file) {
            $dName = 'doc_' . Str::random(8) . '.' . $file->extension();
            $file->storeAs('products/docs', $dName);
            $docs[] = ['name' => $file->getClientOriginalName(), 'file' => $dName];
        }
        $product->documents = array_values($docs);

        $product->save();

        // Переводы EN/KZ + мета
        ProductTranslate::updateOrCreate(
            ['product_id' => $product->id],
            [
                'name_en' => $this->name_en, 'name_kz' => $this->name_kz,
                'description_en' => $this->description_en, 'description_kz' => $this->description_kz,
                'usage_en' => $this->usage_en, 'usage_kz' => $this->usage_kz,
                'char_en' => $this->char_en, 'char_kz' => $this->char_kz,
                'short_description_en' => $this->short_description_en, 'short_description_kz' => $this->short_description_kz,
                'meta_title_en' => $this->meta_title_en, 'meta_title_kz' => $this->meta_title_kz,
                'meta_description_en' => $this->meta_description_en, 'meta_description_kz' => $this->meta_description_kz,
                'meta_keywords_en' => $this->meta_keywords_en, 'meta_keywords_kz' => $this->meta_keywords_kz,
                'seo_h1_en' => $this->seo_h1_en, 'seo_h1_kz' => $this->seo_h1_kz,
            ]
        );

        session()->flash('success', 'Товар «' . $product->name . '» создан.');
        return redirect()->route('products');
    }

    public function render()
    {
        $categories = Category::whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->with('children')])
            ->orderBy('sort_order')->get();
        $brands = Brand::where('status', 0)->orderBy('name')->get();

        return view('livewire.add-product-component', compact('categories', 'brands'))
            ->layout('layouts.admin');
    }
}
