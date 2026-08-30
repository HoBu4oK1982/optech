<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\Project;
use App\Models\Solution;
use App\Models\Product;
use App\Livewire\Concerns\WithRepeaters;
use App\Livewire\Concerns\WithRichText;

class EditProjectComponent extends Component
{
    use WithFileUploads, WithRepeaters, WithRichText;

    public $projectId, $existing_image, $existing_og_image;
    public $title_ru, $title_en, $title_kz, $slug, $status = 0, $sort_order = 0;
    public $description_ru, $description_en, $description_kz;
    public $client, $project_year, $location;
    public array $result_metrics = [];
    public $image, $og_image, $galleryUploads = [];
    public array $gallery = [];
    public array $related_solutions = [];
    public array $related_products = [];
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
            'slug' => 'required|unique:projects,slug,' . $this->projectId,
            'image' => 'nullable|image|max:5120',
            'og_image' => 'nullable|image|max:5120',
            'galleryUploads.*' => 'nullable|image|max:5120',
        ];
    }
    protected $messages = ['slug.unique' => 'Такой URL (slug) уже существует!'];

    public function mount($project_slug)
    {
        $p = Project::where('slug', $project_slug)->firstOrFail();
        $this->projectId = $p->id;
        $this->title_ru = $p->title_ru; $this->title_en = $p->title_en; $this->title_kz = $p->title_kz;
        $this->slug = $p->slug;
        $this->description_ru = $p->description_ru; $this->description_en = $p->description_en; $this->description_kz = $p->description_kz;
        $this->client = $p->client; $this->project_year = $p->project_year; $this->location = $p->location;
        $this->result_metrics = $p->result_metrics ?? [];
        $this->status = $p->status; $this->sort_order = $p->sort_order;
        $this->gallery = $p->gallery ?? [];
        $this->related_solutions = $p->related_solutions ?? [];
        $this->related_products = $p->related_products ?? [];
        $this->related_categories = $p->related_categories ?? [];
        $this->existing_image = $p->image; $this->existing_og_image = $p->og_image;
        foreach (['meta_title','meta_description','meta_keywords','seo_h1','seo_h2','meta_title_en','meta_title_kz','meta_description_en','meta_description_kz','meta_keywords_en','meta_keywords_kz','seo_h1_en','seo_h1_kz','og_title','og_description','image_alt','image_alt_en','image_alt_kz','image_title','canonical_url'] as $f) { $this->{$f} = $p->{$f}; }
        $this->is_indexable = (bool) $p->is_indexable; $this->is_followable = (bool) $p->is_followable;
        $this->in_sitemap = (bool) $p->in_sitemap; $this->sitemap_priority = $p->sitemap_priority ?? 0.5; $this->sitemap_changefreq = $p->sitemap_changefreq ?? 'weekly';
    }

    public function generateSlug() { $this->slug = Str::slug($this->title_ru); }
    public function removeGallery($i) { unset($this->gallery[$i]); $this->gallery = array_values($this->gallery); }

    public function updateProject()
    {
        $this->validate();
        $p = Project::findOrFail($this->projectId);
        $p->title_ru = $this->title_ru; $p->title_en = $this->title_en; $p->title_kz = $this->title_kz;
        $p->slug = $this->slug;
        $p->description_ru = $this->processInlineImages($this->description_ru, 'projects');
        $p->description_en = $this->description_en; $p->description_kz = $this->description_kz;
        $p->client = $this->client; $p->project_year = $this->project_year; $p->location = $this->location;
        $p->result_metrics = array_values(array_filter($this->result_metrics, fn ($m) => ! empty($m['label']) || ! empty($m['value'])));
        $p->status = $this->status ?: 0; $p->sort_order = (int) $this->sort_order;
        $p->related_solutions = array_values($this->related_solutions);
        $p->related_products = array_values($this->related_products);
        $p->related_categories = array_values($this->related_categories);
        foreach (['meta_title','meta_description','meta_keywords','seo_h1','seo_h2','meta_title_en','meta_title_kz','meta_description_en','meta_description_kz','meta_keywords_en','meta_keywords_kz','seo_h1_en','seo_h1_kz','og_title','og_description','image_alt','image_alt_en','image_alt_kz','image_title','canonical_url','sitemap_changefreq'] as $f) { $p->{$f} = $this->{$f}; }
        $p->is_indexable = (bool) $this->is_indexable; $p->is_followable = (bool) $this->is_followable;
        $p->in_sitemap = (bool) $this->in_sitemap; $p->sitemap_priority = $this->sitemap_priority;
        if ($this->image) { $n = Carbon::now()->timestamp . '_' . Str::random(5) . '.' . $this->image->extension(); $this->image->storeAs('projects', $n); $p->image = $n; }
        if ($this->og_image) { $n = 'og_' . Carbon::now()->timestamp . '.' . $this->og_image->extension(); $this->og_image->storeAs('projects', $n); $p->og_image = $n; }
        $g = $this->gallery;
        foreach ($this->galleryUploads as $f) { $gn = 'g_' . Str::random(8) . '.' . $f->extension(); $f->storeAs('projects', $gn); $g[] = $gn; }
        $p->gallery = array_values($g);
        $p->save();
        session()->flash('success', 'Проект обновлён.');
        return redirect()->route('projects');
    }

    public function render()
    {
        return view('livewire.edit-project-component', [
            'solutions' => Solution::orderBy('title_ru')->limit(300)->get(),
            'products' => Product::orderBy('name')->limit(300)->get(),
        ])->layout('layouts.admin');
    }
}
