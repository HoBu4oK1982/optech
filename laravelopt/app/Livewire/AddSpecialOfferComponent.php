<?php

namespace App\Livewire;

use Livewire\Component;
use App\Livewire\Concerns\HasSeoFields;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\SpecialOffer;
use Illuminate\Support\Facades\Auth;
use DOMDocument;

class AddSpecialOfferComponent extends Component
{
    use WithFileUploads;
    use HasSeoFields;

    public $title_ru;
    public $description_ru;
    public $slug;
    public $image;
    public $status;

    protected $rules = [
        'title_ru' => 'required',
        'slug' => 'required|unique:special_offers',
    ];

    protected $messages = [
        'slug.unique' => 'Такой транслит уже существует!',
    ];

    public function updated($slug)
    {
        $this->validateOnly($slug);
    }


    public function generateSlug(){
        $this->slug = Str::slug($this->title_ru, '-');
    }
    
    public function addOffer()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $offer = new SpecialOffer();
        $offer->title_ru = $this->title_ru;
        $offer->slug = $this->slug;
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
                    $image_name = "/upload/offers/" . time() . '.' . $type;
                    $path = public_path() . $image_name;
                    file_put_contents($path, $data);
                    $img->removeAttribute('src');
                    $img->setAttribute('src', $image_name);
                }
                $this->description_ru = $dom->saveHTML($dom->documentElement) . PHP_EOL . PHP_EOL;
            }

            $offer->description_ru = $this->description_ru;
            $offer->status = $this->status == NULL ? 0 : $this->status;
            $this->applySeoFields($offer, 'offers');

            $imageName = Carbon::now()->timestamp . '.' . $this->image->extension();
            $this->image->storeAs('offers', $imageName);
            $offer->image = $imageName;
        }
        $validatedData = $this->validate();
        $offer->save($validatedData);
        return redirect()->route('offers');
    }

    public function render()
    {
        return view('livewire.add-special-offer-component')->layout('layouts.admin');
    }
}


