<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use DOMDocument;
use Illuminate\Support\Facades\File;
use App\Models\Brand;



class EditBrandComponent extends Component
{
    use WithFileUploads;
    public $slug;
    public $status;
    public $name;
    public $image;
    public $newimage;
    public $brand_slug;
    public $brand_id;
    public $meta_description;
    public $meta_keywords;
    public $meta_title;

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

    public function mount($brand_slug){
        $this->brand_slug = $brand_slug;
        $brand = Brand::where('slug', $brand_slug)->first();
        $this->slug = $brand->slug;
        $this->name = $brand->name;
        $this->image = $brand->image;
        $this->status = $brand->status;
        $this->meta_description = $brand->meta_description;
        $this->meta_keywords = $brand->meta_keywords;
        $this->meta_title = $brand->meta_title;
        $this->brand_id = $brand->id;
    }

    public function updateBrand(){

        $brand = Brand::find($this->brand_id);

        if($this->slug === $brand->slug){
            $brand->name = $this->name;
            $brand->status = $this->status;
            $brand->meta_description = $this->meta_description;
            $brand->meta_keywords = $this->meta_keywords;
            $brand->meta_title = $this->meta_title;

            if($this->newimage){
                if($brand->image == NULL){
                    $imageName = Carbon::now()->timestamp . '.' . $this->newimage->extension();
                    $this->newimage->storeAs('brands', $imageName);
                    $brand->image = $imageName;
                } else {
                    $imageName = $brand->image;
                    $this->newimage->storeAs('brands', $imageName);
                    $brand->image = $imageName;
                }
            }
            $brand->save();
            return redirect()->route('brands');
        } else {
            $brand->name = $this->name;
            $brand->status = $this->status;
            $brand->slug = $this->slug;
            $brand->meta_description = $this->meta_description;
            $brand->meta_keywords = $this->meta_keywords;
            $brand->meta_title = $this->meta_title;

            if($this->newimage){
                if($brand->image == NULL){
                    $imageName = Carbon::now()->timestamp . '.' . $this->newimage->extension();
                    $this->newimage->storeAs('brands', $imageName);
                    $brand->image = $imageName;
                } else {
                    $imageName = $brand->image;
                    $this->newimage->storeAs('brands', $imageName);
                    $brand->image = $imageName;
                }
            }
            $validatedData = $this->validate();
            $brand->save($validatedData);
            return redirect()->route('brands');
        }

    }

    public function render()
    {
        return view('livewire.edit-brand-component')->layout('layouts.admin');
    }
}
