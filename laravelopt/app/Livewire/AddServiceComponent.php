<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use DOMDocument;

class AddServiceComponent extends Component
{
    use WithFileUploads;

    public $title_ru;
    public $description_ru;
    public $slug;
    public $image;
    public $status;
    public $meta_keywords;
    public $meta_description;
    public $meta_title;

    protected $rules = [
        'title_ru' => 'required',
        'slug' => 'required|unique:services',
    ];

    protected $messages = [
        'slug.unique' => 'Такой транслит уже существует!',
    ];

    public function updated($slug)
    {
        $this->validateOnly($slug);
    }

    public function generateSlug()
    {
        $this->slug = Str::slug($this->title_ru, '-');
    }

    public function addService()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $service = new Service();
        $service->title_ru = $this->title_ru;
        $service->slug = $this->slug;
        if($this->validate()){
            $dom = new DomDocument();
            @$dom->loadHTML(mb_convert_encoding($this->description_ru, 'HTML-ENTITIES', 'UTF-8'));
            $images = $dom->getElementsByTagName('img');

            if ($images->length > 0) {
                foreach ($images as $key => $img) {
                    $data = $img->getAttribute('src');
                    list($type, $data) = explode(';', $data);
                    list(, $data) = explode(',', $data);
                    $data = base64_decode($data);
                    list(, $type) = explode(':', $type);
                    list(, $type) = explode('/', $type);
                    $image_name = "/upload/services/" . time() . '.' . $type;
                    $path = public_path() . $image_name;
                    file_put_contents($path, $data);
                    $img->removeAttribute('src');
                    $img->setAttribute('src', $image_name);
                }
                $this->description_ru = $dom->saveHTML($dom->documentElement) . PHP_EOL . PHP_EOL;
            }

            $service->description_ru = $this->description_ru;
            $service->status = $this->status == NULL ? 0 : $this->status;
            $service->meta_keywords = $this->meta_keywords;
            $service->meta_title = $this->meta_title;
            $service->meta_description = $this->meta_description;

            $imageName = Carbon::now()->timestamp . '.' . $this->image->extension();
            $this->image->storeAs('services', $imageName);
            $service->image = $imageName;
        }
        $validatedData = $this->validate();
        $service->save($validatedData);
        return redirect()->route('services');
    }

    public function render()
    {
        return view('livewire.add-service-component')->layout('layouts.admin');
    }
}
