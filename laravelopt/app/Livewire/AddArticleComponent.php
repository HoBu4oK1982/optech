<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Livewire\Concerns\WithRepeaters;
use App\Livewire\Concerns\WithRichText;

class AddArticleComponent extends Component
{
    use WithFileUploads, WithRepeaters, WithRichText;

    public $title_ru, $title_en, $title_kz, $slug, $status = 0, $sort_order = 0;
    public $description_ru, $description_en, $description_kz;
    public $excerpt, $excerpt_en, $excerpt_kz;
    public $category_id, $author, $source, $published_at, $reading_time, $tags;
    public $image, $og_image;
    public array $faq = [];
    public array $related_products = [];
    public array $related_categories = [];

    // SEO
    public $meta_title, $meta_description, $meta_keywords, $seo_h1, $seo_h2;
    public $meta_title_en, $meta_title_kz, $meta_description_en, $meta_description_kz;
    public $meta_keywords_en, $meta_keywords_kz, $seo_h1_en, $seo_h1_kz;
    public $og_title, $og_description;
    public $image_alt, $image_alt_en, $image_alt_kz, $image_title;
    public $canonical_url, $is_indexable = true, $is_followable = true;
    public $in_sitemap = true, $sitemap_priority = 0.5, $sitemap_changefreq = 'weekly';

    protected $rules = [
        'title_ru' => 'required|string|max:255',
        'slug' => 'required|unique:articles,slug',
        'image' => 'nullable|image|max:5120',
        'og_image' => 'nullable|image|max:5120',
    ];
    protected $messages = ['slug.unique' => 'Такой URL (slug) уже существует!'];

    public function updated($prop) { $this->validateOnly($prop); }
    public function generateSlug() { $this->slug = Str::slug($this->title_ru); }

    public function addArticle()
    {
        $this->validate();
        $a = new Article();
        $a->title_ru = $this->title_ru; $a->title_en = $this->title_en; $a->title_kz = $this->title_kz;
        $a->slug = $this->slug;
        $a->description_ru = $this->processInlineImages($this->description_ru, 'articles');
        $a->description_en = $this->description_en; $a->description_kz = $this->description_kz;
        $a->excerpt = $this->excerpt; $a->excerpt_en = $this->excerpt_en; $a->excerpt_kz = $this->excerpt_kz;
        $a->category_id = $this->category_id ?: null;
        $a->author = $this->author; $a->source = $this->source;
        $a->published_at = $this->published_at ?: null;
        $a->reading_time = $this->reading_time ?: null;
        $a->tags = $this->tags ? array_values(array_filter(array_map('trim', explode(',', $this->tags)))) : [];
        $a->status = $this->status ?: 0;
        $a->sort_order = (int) $this->sort_order;
        $a->faq = array_values(array_filter($this->faq, fn ($f) => ! empty($f['question'])));
        $a->related_products = array_values($this->related_products);
        $a->related_categories = array_values($this->related_categories);

        $this->fillSeo($a);

        if ($this->image) {
            $n = Carbon::now()->timestamp . '_' . Str::random(5) . '.' . $this->image->extension();
            $this->image->storeAs('articles', $n); $a->image = $n;
        }
        if ($this->og_image) {
            $n = 'og_' . Carbon::now()->timestamp . '.' . $this->og_image->extension();
            $this->og_image->storeAs('articles', $n); $a->og_image = $n;
        }
        $a->save();

        session()->flash('success', 'Статья создана.');
        return redirect()->route('articles');
    }

    protected function fillSeo($a)
    {
        foreach ([
            'meta_title','meta_description','meta_keywords','seo_h1','seo_h2',
            'meta_title_en','meta_title_kz','meta_description_en','meta_description_kz',
            'meta_keywords_en','meta_keywords_kz','seo_h1_en','seo_h1_kz',
            'og_title','og_description','image_alt','image_alt_en','image_alt_kz','image_title',
            'canonical_url','sitemap_changefreq',
        ] as $f) { $a->{$f} = $this->{$f}; }
        $a->is_indexable = (bool) $this->is_indexable;
        $a->is_followable = (bool) $this->is_followable;
        $a->in_sitemap = (bool) $this->in_sitemap;
        $a->sitemap_priority = $this->sitemap_priority;
    }

    public function render()
    {
        return view('livewire.add-article-component', [
            'categories' => Category::orderBy('name')->get(),
            'products' => Product::orderBy('name')->limit(300)->get(),
        ])->layout('layouts.admin');
    }
}
