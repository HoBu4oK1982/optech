<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Brand;


class AddBrandComponent extends Component
{
    use WithFileUploads;
    public $name;
    public $slug;
    public $status;
    public $meta_description;
    public $meta_keywords;
    public $meta_title;
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
            $brand->meta_description = $this->meta_description;
            $brand->meta_keywords = $this->meta_keywords;
            $brand->meta_title = $this->meta_title;
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
