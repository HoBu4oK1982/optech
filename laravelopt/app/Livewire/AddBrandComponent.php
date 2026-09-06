<?php

namespace App\Livewire;

use Livewire\Component;
use App\Livewire\Concerns\HasSeoFields;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Brand;


class AddBrandComponent extends Component
{
    use WithFileUploads;
    use HasSeoFields;

    // Страница бренда — листинг товаров, как категория: у неё есть свои
    // SEO-тексты над и под сеткой и отдельный заголовок в хлебных крошках.
    // У услуг и спецпредложений таких колонок нет, поэтому поля объявлены
    // здесь, а не в общем трейте.
    public $seo_text_top, $seo_text_top_en, $seo_text_top_kz;
    public $seo_text_bottom, $seo_text_bottom_en, $seo_text_bottom_kz;
    public $breadcrumb_title;

    protected function extraSeoFields(): array
    {
        return [
            'seo_text_top', 'seo_text_top_en', 'seo_text_top_kz',
            'seo_text_bottom', 'seo_text_bottom_en', 'seo_text_bottom_kz',
            'breadcrumb_title',
        ];
    }
    public $name;
    public $slug;
    public $status;
    public $image;

    protected $rules = [
        'name' => 'required',
        'slug' => 'required|unique:brands',
    ];

    protected $messages = [
        'slug.unique' => 'Такой транслит уже существует!',
    ];

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function generateSlug(){
        $this->slug = Str::slug($this->name, '-');
    }

    public function addSolution(){
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if($this->validate()){
            $brand = new Brand();  
            $brand->status = $this->status == NULL ? 0 : $this->status; 
            $brand->name = $this->name;
            $brand->slug = $this->slug;
            $this->applySeoFields($brand, 'brands');
            $imageName = Carbon::now()->timestamp . '.' . $this->image->extension();
            $this->image->storeAs('brands', $imageName);
            $brand->image = $imageName;
        }
        $validatedData = $this->validate();
        $brand->save($validatedData);
        return redirect()->route('brands');
    }



    public function render()
    {
        return view('livewire.add-brand-component')->layout('layouts.admin');
    }
}
