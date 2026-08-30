<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\SolutionCategory;
use App\Livewire\Concerns\WithRepeaters;
use App\Livewire\Concerns\WithRichText;

class AddSolutionCategoryComponent extends Component
{
    use WithFileUploads, WithRepeaters, WithRichText;

    public $title_ru, $title_en, $title_kz, $slug, $status = 0, $sort_order = 0, $icon;
    public $description_ru, $description_en, $description_kz;
    public $seo_text_top, $seo_text_top_en, $seo_text_top_kz;
    public $seo_text_bottom, $seo_text_bottom_en, $seo_text_bottom_kz;
    public $image, $og_image;
    public array $faq = [];

    public $meta_title, $meta_description, $meta_keywords, $seo_h1, $seo_h2;
    public $meta_title_en, $meta_title_kz, $meta_description_en, $meta_description_kz;
    public $meta_keywords_en, $meta_keywords_kz, $seo_h1_en, $seo_h1_kz;
    public $og_title, $og_description, $image_alt, $image_alt_en, $image_alt_kz, $image_title;
    public $canonical_url, $is_indexable = true, $is_followable = true;
    public $in_sitemap = true, $sitemap_priority = 0.5, $sitemap_changefreq = 'weekly';

    protected $rules = [
        'title_ru' => 'required|string|max:255',
        'slug' => 'required|unique:solution_categories,slug',
        'image' => 'nullable|image|max:5120',
        'og_image' => 'nullable|image|max:5120',
    ];
    protected $messages = ['slug.unique' => 'Такой URL (slug) уже существует!'];

    public function updated($p) { $this->validateOnly($p); }
    public function generateSlug() { $this->slug = Str::slug($this->title_ru); }

    public function addSolutionCategory()
    {
        $this->validate();
        $c = new SolutionCategory();
        $c->title_ru = $this->title_ru; $c->title_en = $this->title_en; $c->title_kz = $this->title_kz;
        $c->slug = $this->slug;
        $c->description_ru = $this->processInlineImages($this->description_ru, 'solcategories');
        $c->description_en = $this->description_en; $c->description_kz = $this->description_kz;
        $c->seo_text_top = $this->seo_text_top; $c->seo_text_top_en = $this->seo_text_top_en; $c->seo_text_top_kz = $this->seo_text_top_kz;
        $c->seo_text_bottom = $this->seo_text_bottom; $c->seo_text_bottom_en = $this->seo_text_bottom_en; $c->seo_text_bottom_kz = $this->seo_text_bottom_kz;
        $c->icon = $this->icon; $c->status = $this->status ?: 0; $c->sort_order = (int) $this->sort_order;
        $c->faq = array_values(array_filter($this->faq, fn ($f) => ! empty($f['question'])));
        $this->fillSeo($c);
        if ($this->image) { $n = Carbon::now()->timestamp . '_' . Str::random(5) . '.' . $this->image->extension(); $this->image->storeAs('solcategories', $n); $c->image = $n; }
        if ($this->og_image) { $n = 'og_' . Carbon::now()->timestamp . '.' . $this->og_image->extension(); $this->og_image->storeAs('solcategories', $n); $c->og_image = $n; }
        $c->save();
        session()->flash('success', 'Категория решений создана.');
        return redirect()->route('solcategories');
    }

    protected function fillSeo($c)
    {
        foreach (['meta_title','meta_description','meta_keywords','seo_h1','seo_h2','meta_title_en','meta_title_kz','meta_description_en','meta_description_kz','meta_keywords_en','meta_keywords_kz','seo_h1_en','seo_h1_kz','og_title','og_description','image_alt','image_alt_en','image_alt_kz','image_title','canonical_url','sitemap_changefreq'] as $f) { $c->{$f} = $this->{$f}; }
        $c->is_indexable = (bool) $this->is_indexable; $c->is_followable = (bool) $this->is_followable;
        $c->in_sitemap = (bool) $this->in_sitemap; $c->sitemap_priority = $this->sitemap_priority;
    }

    public function render()
    {
        return view('livewire.add-solution-category-component')->layout('layouts.admin');
    }
}
