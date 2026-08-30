<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\Category;
use App\Livewire\Concerns\WithRepeaters;

class EditCategoryComponent extends Component
{
    use WithFileUploads, WithRepeaters;

    public $categoryId, $existing_image, $existing_og_image;

    public $name, $name_en, $name_kz, $slug, $parent_id, $status = 0, $sort_order = 0;
    public $description, $description_en, $description_kz;
    public $image, $og_image, $icon, $breadcrumb_title;

    public $seo_text_top, $seo_text_top_en, $seo_text_top_kz;
    public $seo_text_bottom, $seo_text_bottom_en, $seo_text_bottom_kz;

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
            'slug'  => 'required|unique:categories,slug,' . $this->categoryId,
            'image' => 'nullable|image|max:5120',
            'og_image' => 'nullable|image|max:5120',
        ];
    }

    protected $messages = ['slug.unique' => 'Такой URL (slug) уже существует!'];

    public function mount($category_slug)
    {
        $c = Category::where('slug', $category_slug)->firstOrFail();

        $this->categoryId = $c->id;
        $this->name = $c->name; $this->name_en = $c->name_en; $this->name_kz = $c->name_kz;
        $this->slug = $c->slug;
        $this->description = $c->description; $this->description_en = $c->description_en; $this->description_kz = $c->description_kz;
        $this->status = $c->status;
        $this->parent_id = $c->parent_id;
        $this->sort_order = $c->sort_order;
        $this->icon = $c->icon;
        $this->breadcrumb_title = $c->breadcrumb_title;
        $this->existing_image = $c->image;
        $this->existing_og_image = $c->og_image;

        $this->seo_text_top = $c->seo_text_top; $this->seo_text_top_en = $c->seo_text_top_en; $this->seo_text_top_kz = $c->seo_text_top_kz;
        $this->seo_text_bottom = $c->seo_text_bottom; $this->seo_text_bottom_en = $c->seo_text_bottom_en; $this->seo_text_bottom_kz = $c->seo_text_bottom_kz;
        $this->faq = $c->faq ?? [];

        $this->meta_title = $c->meta_title; $this->meta_description = $c->meta_description; $this->meta_keywords = $c->meta_keywords; $this->seo_h1 = $c->seo_h1;
        $this->meta_title_en = $c->meta_title_en; $this->meta_title_kz = $c->meta_title_kz;
        $this->meta_description_en = $c->meta_description_en; $this->meta_description_kz = $c->meta_description_kz;
        $this->meta_keywords_en = $c->meta_keywords_en; $this->meta_keywords_kz = $c->meta_keywords_kz;
        $this->seo_h1_en = $c->seo_h1_en; $this->seo_h1_kz = $c->seo_h1_kz;
        $this->canonical_url = $c->canonical_url;
        $this->is_indexable = (bool) $c->is_indexable;
        $this->is_followable = (bool) $c->is_followable;
        $this->og_title = $c->og_title;
        $this->og_description = $c->og_description;
        $this->seo_h2 = $c->seo_h2;
        $this->image_alt = $c->image_alt;
        $this->image_alt_en = $c->image_alt_en;
        $this->image_alt_kz = $c->image_alt_kz;
        $this->image_title = $c->image_title;
        $this->in_sitemap = (bool) $c->in_sitemap;
        $this->sitemap_priority = $c->sitemap_priority ?? 0.5;
        $this->sitemap_changefreq = $c->sitemap_changefreq ?? 'weekly';
    }

    public function generateSlug()
    {
        $this->slug = Str::slug($this->name);
    }

    public function updateCategory()
    {
        $this->validate();

        $c = Category::findOrFail($this->categoryId);
        $c->name = $this->name; $c->name_en = $this->name_en; $c->name_kz = $this->name_kz;
        $c->slug = $this->slug;
        $c->description = $this->description ?: null;
        $c->description_en = $this->description_en ?: null;
        $c->description_kz = $this->description_kz ?: null;
        $c->status = $this->status ?: 0;
        $c->parent_id = $this->parent_id ?: null;
        $c->sort_order = (int) $this->sort_order;
        $c->icon = $this->icon;
        $c->breadcrumb_title = $this->breadcrumb_title;

        $c->seo_text_top = $this->seo_text_top; $c->seo_text_top_en = $this->seo_text_top_en; $c->seo_text_top_kz = $this->seo_text_top_kz;
        $c->seo_text_bottom = $this->seo_text_bottom; $c->seo_text_bottom_en = $this->seo_text_bottom_en; $c->seo_text_bottom_kz = $this->seo_text_bottom_kz;
        $c->faq = array_values(array_filter($this->faq, fn ($f) => ! empty($f['question'])));

        $c->meta_title = $this->meta_title; $c->meta_description = $this->meta_description; $c->meta_keywords = $this->meta_keywords; $c->seo_h1 = $this->seo_h1;
        $c->meta_title_en = $this->meta_title_en; $c->meta_title_kz = $this->meta_title_kz;
        $c->meta_description_en = $this->meta_description_en; $c->meta_description_kz = $this->meta_description_kz;
        $c->meta_keywords_en = $this->meta_keywords_en; $c->meta_keywords_kz = $this->meta_keywords_kz;
        $c->seo_h1_en = $this->seo_h1_en; $c->seo_h1_kz = $this->seo_h1_kz;
        $c->canonical_url = $this->canonical_url;
        $c->is_indexable = (bool) $this->is_indexable;
        $c->is_followable = (bool) $this->is_followable;
        $c->og_title = $this->og_title;
        $c->og_description = $this->og_description;
        $c->seo_h2 = $this->seo_h2;
        $c->image_alt = $this->image_alt;
        $c->image_alt_en = $this->image_alt_en;
        $c->image_alt_kz = $this->image_alt_kz;
        $c->image_title = $this->image_title;
        $c->in_sitemap = (bool) $this->in_sitemap;
        $c->sitemap_priority = $this->sitemap_priority;
        $c->sitemap_changefreq = $this->sitemap_changefreq;

        if ($this->image) {
            $imageName = Carbon::now()->timestamp . '_' . Str::random(5) . '.' . $this->image->extension();
            $this->image->storeAs('categories', $imageName);
            $c->image = $imageName;
        }
        if ($this->og_image) {
            $ogName = 'og_' . Carbon::now()->timestamp . '.' . $this->og_image->extension();
            $this->og_image->storeAs('categories', $ogName);
            $c->og_image = $ogName;
        }

        $c->save();

        session()->flash('success', 'Категория «' . $c->name . '» обновлена.');
        return redirect()->route('categories');
    }

    public function render()
    {
        $categories = Category::whereNull('parent_id')
            ->where('id', '!=', $this->categoryId)
            ->with(['children' => fn ($q) => $q->with('children')])
            ->orderBy('sort_order')->get();

        return view('livewire.edit-category-component', compact('categories'))
            ->layout('layouts.admin');
    }
}
