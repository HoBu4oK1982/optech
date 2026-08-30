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

class EditProductComponent extends Component
{
    use WithFileUploads, WithRepeaters;

    public $productId;
    public $existing_image, $existing_og_image;

    public $name, $slug, $SKU, $category_id, $brand_id, $status = 0, $sort_order = 0;
    public $description, $char, $usage, $short_description;
    public $name_en, $name_kz, $description_en, $description_kz;
    public $usage_en, $usage_kz, $char_en, $char_kz;
    public $short_description_en, $short_description_kz;

    public $price, $old_price, $currency = 'KZT', $price_on_request = false;
    public $availability = 'InStock', $gtin, $mpn;

    public $image, $galleryUploads = [];
    public array $gallery = [];
    public $documentUploads = [];
    public array $documents = [];
    public $og_image, $video_url;

    public array $faq = [];

    public $meta_title, $meta_description, $meta_keywords, $seo_h1;
    public $meta_title_en, $meta_title_kz, $meta_description_en, $meta_description_kz;
    public $meta_keywords_en, $meta_keywords_kz, $seo_h1_en, $seo_h1_kz;
    public $canonical_url, $is_indexable = true;
    // ТЗ: OG, follow, alt/title, H2, sitemap
    public $og_title, $og_description, $seo_h2;
    public $image_alt, $image_alt_en, $image_alt_kz, $image_title;
    public $is_followable = true, $in_sitemap = true, $sitemap_priority = 0.5, $sitemap_changefreq = 'weekly';

    public function rules()
    {
        return [
            'name'  => 'required|string|max:255',
            'slug'  => 'required|unique:products,slug,' . $this->productId,
            'price' => 'nullable|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:5120',
            'og_image' => 'nullable|image|max:5120',
            'galleryUploads.*' => 'nullable|image|max:5120',
        ];
    }

    protected $messages = ['slug.unique' => 'Такой URL (slug) уже существует!'];

    public function mount($product_slug)
    {
        $product = Product::where('slug', $product_slug)->firstOrFail();
        $t = ProductTranslate::where('product_id', $product->id)->first();

        $this->productId = $product->id;
        $this->name = $product->name;
        $this->slug = $product->slug;
        $this->SKU = $product->SKU;
        $this->category_id = $product->category_id;
        $this->brand_id = $product->brand_id;
        $this->status = $product->status;
        $this->sort_order = $product->sort_order;
        $this->description = $product->description;
        $this->char = $product->char;
        $this->usage = $product->usage;
        $this->short_description = $product->short_description;

        $this->price = $product->price;
        $this->old_price = $product->old_price;
        $this->currency = $product->currency ?: 'KZT';
        $this->price_on_request = (bool) $product->price_on_request;
        $this->availability = $product->availability ?: 'InStock';
        $this->gtin = $product->gtin;
        $this->mpn = $product->mpn;
        $this->video_url = $product->video_url;
        $this->faq = $product->faq ?? [];
        $this->gallery = $product->gallery ?? [];
        $this->documents = $product->documents ?? [];
        $this->existing_image = $product->image;
        $this->existing_og_image = $product->og_image;

        $this->meta_title = $product->meta_title;
        $this->meta_description = $product->meta_description;
        $this->meta_keywords = $product->meta_keywords;
        $this->seo_h1 = $product->seo_h1;
        $this->canonical_url = $product->canonical_url;
        $this->is_indexable = (bool) $product->is_indexable;
        $this->is_followable = (bool) $product->is_followable;
        $this->og_title = $product->og_title;
        $this->og_description = $product->og_description;
        $this->seo_h2 = $product->seo_h2;
        $this->image_alt = $product->image_alt;
        $this->image_alt_en = $product->image_alt_en;
        $this->image_alt_kz = $product->image_alt_kz;
        $this->image_title = $product->image_title;
        $this->in_sitemap = (bool) $product->in_sitemap;
        $this->sitemap_priority = $product->sitemap_priority ?? 0.5;
        $this->sitemap_changefreq = $product->sitemap_changefreq ?? 'weekly';

        if ($t) {
            $this->name_en = $t->name_en; $this->name_kz = $t->name_kz;
            $this->description_en = $t->description_en; $this->description_kz = $t->description_kz;
            $this->usage_en = $t->usage_en; $this->usage_kz = $t->usage_kz;
            $this->char_en = $t->char_en; $this->char_kz = $t->char_kz;
            $this->short_description_en = $t->short_description_en; $this->short_description_kz = $t->short_description_kz;
            $this->meta_title_en = $t->meta_title_en; $this->meta_title_kz = $t->meta_title_kz;
            $this->meta_description_en = $t->meta_description_en; $this->meta_description_kz = $t->meta_description_kz;
            $this->meta_keywords_en = $t->meta_keywords_en; $this->meta_keywords_kz = $t->meta_keywords_kz;
            $this->seo_h1_en = $t->seo_h1_en; $this->seo_h1_kz = $t->seo_h1_kz;
        }
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

    private function processInlineImages(?string $html): ?string
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
            $fileName = '/upload/products/' . Str::random(8) . time() . '.' . $type;
            if (! is_dir(public_path('/upload/products'))) {
                @mkdir(public_path('/upload/products'), 0775, true);
            }
            file_put_contents(public_path() . $fileName, base64_decode($content));
            $img->setAttribute('src', $fileName);
        }
        return $dom->saveHTML();
    }

    public function updateProduct()
    {
        $this->validate();

        $product = Product::findOrFail($this->productId);
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

        $product->price = $this->price_on_request ? null : $this->price;
        $product->old_price = $this->old_price;
        $product->currency = $this->currency ?: 'KZT';
        $product->price_on_request = (bool) $this->price_on_request;
        $product->availability = $this->availability ?: 'InStock';
        $product->gtin = $this->gtin;
        $product->mpn = $this->mpn;
        $product->video_url = $this->video_url;
        $product->faq = array_values(array_filter($this->faq, fn ($f) => ! empty($f['question'])));

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

        if ($this->image) {
            $imageName = Carbon::now()->timestamp . '_' . Str::random(5) . '.' . $this->image->extension();
            $this->image->storeAs('products', $imageName);
            $product->image = $imageName;
        }
        if ($this->og_image) {
            $ogName = 'og_' . Carbon::now()->timestamp . '.' . $this->og_image->extension();
            $this->og_image->storeAs('products', $ogName);
            $product->og_image = $ogName;
        }

        $gallery = $this->gallery;
        foreach ($this->galleryUploads as $file) {
            $gName = 'g_' . Str::random(8) . '.' . $file->extension();
            $file->storeAs('products', $gName);
            $gallery[] = $gName;
        }
        $product->gallery = array_values($gallery);

        $docs = $this->documents;
        foreach ($this->documentUploads as $file) {
            $dName = 'doc_' . Str::random(8) . '.' . $file->extension();
            $file->storeAs('products/docs', $dName);
            $docs[] = ['name' => $file->getClientOriginalName(), 'file' => $dName];
        }
        $product->documents = array_values($docs);

        $product->save();

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

        session()->flash('success', 'Товар «' . $product->name . '» обновлён.');
        return redirect()->route('products');
    }

    public function render()
    {
        $categories = Category::whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->with('children')])
            ->orderBy('sort_order')->get();
        $brands = Brand::where('status', 0)->orderBy('name')->get();

        return view('livewire.edit-product-component', compact('categories', 'brands'))
            ->layout('layouts.admin');
    }
}
