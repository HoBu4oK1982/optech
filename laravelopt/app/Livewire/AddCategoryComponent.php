<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use App\Livewire\Concerns\WithRepeaters;

class AddCategoryComponent extends Component
{
    use WithFileUploads, WithRepeaters;

    public $name, $name_en, $name_kz, $slug, $parent_id, $status = 0, $sort_order = 0;
    public $description, $description_en, $description_kz;
    public $image, $og_image, $icon, $breadcrumb_title;

    // SEO-тексты листинга
    public $seo_text_top, $seo_text_top_en, $seo_text_top_kz;
    public $seo_text_bottom, $seo_text_bottom_en, $seo_text_bottom_kz;

    public array $faq = [];

    // SEO meta
    public $meta_title, $meta_description, $meta_keywords, $seo_h1;
    public $meta_title_en, $meta_title_kz, $meta_description_en, $meta_description_kz;
    public $meta_keywords_en, $meta_keywords_kz, $seo_h1_en, $seo_h1_kz;
    public $canonical_url, $is_indexable = true;
    // ТЗ: OG, follow, alt/title, H2, sitemap
    public $og_title, $og_description, $seo_h2;
    public $image_alt, $image_alt_en, $image_alt_kz, $image_title;
    public $is_followable = true, $in_sitemap = true, $sitemap_priority = 0.5, $sitemap_changefreq = 'weekly';

    protected $rules = [
        'name'  => 'required|string|max:255',
        'slug'  => 'required|unique:categories,slug',
        'image' => 'nullable|image|max:5120',
        'og_image' => 'nullable|image|max:5120',
    ];

    protected $messages = ['slug.unique' => 'Такой URL (slug) уже существует!'];

    public function updated($prop)
    {
        $this->validateOnly($prop);
    }

    public function generateSlug()
    {
        $this->slug = Str::slug($this->name);
    }

    public function addCategory()
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }
        $this->validate();

        $category = new Category();
        $category->name = $this->name;
        $category->name_en = $this->name_en;
        $category->name_kz = $this->name_kz;
        $category->slug = $this->slug;
        $category->description = $this->description ?: null;
        $category->description_en = $this->description_en ?: null;
        $category->description_kz = $this->description_kz ?: null;
        $category->status = $this->status ?: 0;
        $category->parent_id = $this->parent_id ?: null;
        $category->sort_order = (int) $this->sort_order;
        $category->icon = $this->icon;
        $category->breadcrumb_title = $this->breadcrumb_title;

        // SEO-тексты
        $category->seo_text_top = $this->seo_text_top;
        $category->seo_text_top_en = $this->seo_text_top_en;
        $category->seo_text_top_kz = $this->seo_text_top_kz;
        $category->seo_text_bottom = $this->seo_text_bottom;
        $category->seo_text_bottom_en = $this->seo_text_bottom_en;
        $category->seo_text_bottom_kz = $this->seo_text_bottom_kz;
        $category->faq = array_values(array_filter($this->faq, fn ($f) => ! empty($f['question'])));

        // Meta
        $category->meta_title = $this->meta_title;
        $category->meta_description = $this->meta_description;
        $category->meta_keywords = $this->meta_keywords;
        $category->seo_h1 = $this->seo_h1;
        $category->meta_title_en = $this->meta_title_en;
        $category->meta_title_kz = $this->meta_title_kz;
        $category->meta_description_en = $this->meta_description_en;
        $category->meta_description_kz = $this->meta_description_kz;
        $category->meta_keywords_en = $this->meta_keywords_en;
        $category->meta_keywords_kz = $this->meta_keywords_kz;
        $category->seo_h1_en = $this->seo_h1_en;
        $category->seo_h1_kz = $this->seo_h1_kz;
        $category->canonical_url = $this->canonical_url;
        $category->is_indexable = (bool) $this->is_indexable;
        $category->is_followable = (bool) $this->is_followable;
        $category->og_title = $this->og_title;
        $category->og_description = $this->og_description;
        $category->seo_h2 = $this->seo_h2;
        $category->image_alt = $this->image_alt;
        $category->image_alt_en = $this->image_alt_en;
        $category->image_alt_kz = $this->image_alt_kz;
        $category->image_title = $this->image_title;
        $category->in_sitemap = (bool) $this->in_sitemap;
        $category->sitemap_priority = $this->sitemap_priority;
        $category->sitemap_changefreq = $this->sitemap_changefreq;

        if ($this->image) {
            $imageName = Carbon::now()->timestamp . '_' . Str::random(5) . '.' . $this->image->extension();
            $this->image->storeAs('categories', $imageName);
            $category->image = $imageName;
        }
        if ($this->og_image) {
            $ogName = 'og_' . Carbon::now()->timestamp . '.' . $this->og_image->extension();
            $this->og_image->storeAs('categories', $ogName);
            $category->og_image = $ogName;
        }

        $category->save();

        session()->flash('success', 'Категория «' . $category->name . '» создана.');
        return redirect()->route('categories');
    }

    public function render()
    {
        $categories = Category::whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->with('children')])
            ->orderBy('sort_order')->get();

        return view('livewire.add-category-component', compact('categories'))
            ->layout('layouts.admin');
    }
}
