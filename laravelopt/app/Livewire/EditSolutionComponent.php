<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\Solution;
use App\Models\SolutionCategory;
use App\Models\Product;
use App\Livewire\Concerns\WithRepeaters;
use App\Livewire\Concerns\WithRichText;

class EditSolutionComponent extends Component
{
    use WithFileUploads, WithRepeaters, WithRichText;

    public $solutionId, $existing_image, $existing_og_image;
    public $title_ru, $title_en, $title_kz, $slug, $category_id, $status = 0, $sort_order = 0, $is_featured = false;
    public $description_ru, $description_en, $description_kz;
    public $image, $og_image, $galleryUploads = [];
    public array $gallery = [];
    public array $faq = [];
    public array $related_products = [];
    public array $related_solutions = [];
    public array $related_categories = [];

    public $meta_title, $meta_description, $meta_keywords, $seo_h1, $seo_h2;
    public $meta_title_en, $meta_title_kz, $meta_description_en, $meta_description_kz;
    public $meta_keywords_en, $meta_keywords_kz, $seo_h1_en, $seo_h1_kz;
    public $og_title, $og_description, $image_alt, $image_alt_en, $image_alt_kz, $image_title;
    public $canonical_url, $is_indexable = true, $is_followable = true;
    public $in_sitemap = true, $sitemap_priority = 0.5, $sitemap_changefreq = 'weekly';

    public function rules()
    {
        return [
            'title_ru' => 'required|string|max:255',
            'slug' => 'required|unique:solutions,slug,' . $this->solutionId,
            'image' => 'nullable|image|max:5120',
            'og_image' => 'nullable|image|max:5120',
            'galleryUploads.*' => 'nullable|image|max:5120',
        ];
    }
    protected $messages = ['slug.unique' => 'Такой URL (slug) уже существует!'];

    public function mount($solution_slug)
    {
        $s = Solution::where('slug', $solution_slug)->firstOrFail();
        $this->solutionId = $s->id;
        $this->title_ru = $s->title_ru; $this->title_en = $s->title_en; $this->title_kz = $s->title_kz;
        $this->slug = $s->slug; $this->category_id = $s->category_id;
        $this->description_ru = $s->description_ru; $this->description_en = $s->description_en; $this->description_kz = $s->description_kz;
        $this->status = $s->status; $this->sort_order = $s->sort_order; $this->is_featured = (bool) $s->is_featured;
        $this->gallery = $s->gallery ?? []; $this->faq = $s->faq ?? [];
        $this->related_products = $s->related_products ?? [];
        $this->related_solutions = $s->related_solutions ?? [];
        $this->related_categories = $s->related_categories ?? [];
        $this->existing_image = $s->image; $this->existing_og_image = $s->og_image;
        foreach (['meta_title','meta_description','meta_keywords','seo_h1','seo_h2','meta_title_en','meta_title_kz','meta_description_en','meta_description_kz','meta_keywords_en','meta_keywords_kz','seo_h1_en','seo_h1_kz','og_title','og_description','image_alt','image_alt_en','image_alt_kz','image_title','canonical_url'] as $f) { $this->{$f} = $s->{$f}; }
        $this->is_indexable = (bool) $s->is_indexable; $this->is_followable = (bool) $s->is_followable;
        $this->in_sitemap = (bool) $s->in_sitemap; $this->sitemap_priority = $s->sitemap_priority ?? 0.5; $this->sitemap_changefreq = $s->sitemap_changefreq ?? 'weekly';
    }

    public function generateSlug() { $this->slug = Str::slug($this->title_ru); }
    public function removeGallery($i) { unset($this->gallery[$i]); $this->gallery = array_values($this->gallery); }

    public function updateSolution()
    {
        $this->validate();
        $s = Solution::findOrFail($this->solutionId);
        $s->title_ru = $this->title_ru; $s->title_en = $this->title_en; $s->title_kz = $this->title_kz;
        $s->slug = $this->slug; $s->category_id = $this->category_id ?: null;
        $s->description_ru = $this->processInlineImages($this->description_ru, 'solutions');
        $s->description_en = $this->description_en; $s->description_kz = $this->description_kz;
        $s->status = $this->status ?: 0; $s->sort_order = (int) $this->sort_order; $s->is_featured = (bool) $this->is_featured;
        $s->faq = array_values(array_filter($this->faq, fn ($f) => ! empty($f['question'])));
        $s->related_products = array_values($this->related_products);
        $s->related_solutions = array_values($this->related_solutions);
        $s->related_categories = array_values($this->related_categories);
        foreach (['meta_title','meta_description','meta_keywords','seo_h1','seo_h2','meta_title_en','meta_title_kz','meta_description_en','meta_description_kz','meta_keywords_en','meta_keywords_kz','seo_h1_en','seo_h1_kz','og_title','og_description','image_alt','image_alt_en','image_alt_kz','image_title','canonical_url','sitemap_changefreq'] as $f) { $s->{$f} = $this->{$f}; }
        $s->is_indexable = (bool) $this->is_indexable; $s->is_followable = (bool) $this->is_followable;
        $s->in_sitemap = (bool) $this->in_sitemap; $s->sitemap_priority = $this->sitemap_priority;
        if ($this->image) { $n = Carbon::now()->timestamp . '_' . Str::random(5) . '.' . $this->image->extension(); $this->image->storeAs('solutions', $n); $s->image = $n; }
        if ($this->og_image) { $n = 'og_' . Carbon::now()->timestamp . '.' . $this->og_image->extension(); $this->og_image->storeAs('solutions', $n); $s->og_image = $n; }
        $g = $this->gallery;
        foreach ($this->galleryUploads as $f) { $gn = 'g_' . Str::random(8) . '.' . $f->extension(); $f->storeAs('solutions', $gn); $g[] = $gn; }
        $s->gallery = array_values($g);
        $s->save();
        session()->flash('success', 'Решение обновлено.');
        return redirect()->route('solutions');
    }

    public function render()
    {
        return view('livewire.edit-solution-component', [
            'solCategories' => SolutionCategory::orderBy('title_ru')->get(),
            'products' => Product::orderBy('name')->limit(300)->get(),
            'solutions' => Solution::where('id', '!=', $this->solutionId)->orderBy('title_ru')->limit(300)->get(),
        ])->layout('layouts.admin');
    }
}
