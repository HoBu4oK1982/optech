<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Carbon\Carbon;
use App\Models\SpecialOffer;
use Illuminate\Support\Facades\Auth;
use DOMDocument;
use Illuminate\Support\Facades\File;

class EditSpecialOfferComponent extends Component
{
    use WithFileUploads;
    public $title_ru;
    public $title_kz;
    public $title_en;
    public $description_ru;
    public $description_kz;
    public $description_en;
    public $slug;
    public $image;
    public $status;
    public $meta_keywords;
    public $meta_description;
    public $meta_title;
    public $newimage;
    public $beforeUpdate;
    public $afterUpdate;
    public $offer_id;

    public function mount($offer_slug){
        $offer = SpecialOffer::where('slug', $offer_slug)->first();
        $this->offer_id = $offer->id;
        $this->title_ru = $offer->title_ru;
        $this->title_en = $offer->title_en;
        $this->title_kz = $offer->title_kz;
        $this->slug = $offer->slug;
        $this->image = $offer->image;
        $this->description_ru = $offer->description_ru;
        $this->description_en = $offer->description_en;
        $this->description_kz = $offer->description_kz;
        $this->status = $offer->status;
        $this->meta_keywords = $offer->meta_keywords;
        $this->meta_title = $offer->meta_title;
        $this->meta_description = $offer->meta_description;
    }

    protected $rules = [
        'title_ru' => 'required',
        'slug' => 'required|unique:special_offers',
    ];

    protected $messages = [
        'slug.unique' => 'Такой транслит уже существует!',
    ];

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function generateSlug(){
        $this->slug = Str::slug($this->title_ru, '-');
    }

    public function updateOffer(){
        $offer = SpecialOffer::find($this->offer_id);
        if($this->slug === $offer->slug){
            $offer->title_ru = $this->title_ru;
            $offer->title_en = $this->title_en;
            $offer->title_kz = $this->title_kz;
            $offer->slug = $this->slug;        
            $dom = new DOMDocument();
            @$dom->loadHTML($offer->description_ru, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $descriptionImages = $dom->getElementsByTagName('img');

            $this->beforeUpdate = array();
            if($descriptionImages->length > 0)
            {          
                foreach ($descriptionImages as $img) {
                    $this->beforeUpdate[] = $img->getAttribute('src');                
                }      
            }
            
            $dom = new DomDocument();
            @$dom->loadHTML(mb_convert_encoding($this->description_ru, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $images = $dom->getElementsByTagName('img');
    

            $this->afterUpdate = array();
            if($images->length > 0)
            {
                foreach ($images as $key => $img) {
                    
                    $pattern = "/^\/upload\/offers\/.+$/";
                    $data = $img->getAttribute('src');                
                    
                    if(!preg_match($pattern, $data))
                    {

                        list($type, $data) = explode(';', $data);
                        list(, $data) = explode(',', $data);
                        $data = base64_decode($data);
                        list(, $type) = explode(':', $type);
                        list(, $type) = explode('/', $type); 
                        
                        $image_name = "/upload/offers/" . time(). $key . '.' . $type;
                        $path = public_path() . $image_name;
                        file_put_contents($path, $data);
                        
                        $img->removeAttribute('src');
                        $img->setAttribute('src', $image_name);
                        
                    } else {
                        $this->afterUpdate[] = $data;
                    }
                }

                $this->description_ru = $dom->saveHTML($dom->documentElement) . PHP_EOL . PHP_EOL;
            
            }
            
            foreach ($this->beforeUpdate as $path) {
                
                if(!in_array($path, $this->afterUpdate))
                {
                    $file = public_path($path);
                    
                    if(File::exists($file))
                    {
                        File::delete($file);
                    }
                }
                
            }     
            $offer->description_ru = $this->description_ru;
            $offer->description_en = $this->description_en;
            $offer->description_kz = $this->description_kz;
            $offer->status = $this->status;

            if($this->newimage){
                $imageName = $offer->image;
                $this->newimage->storeAs('offers', $imageName);
                $offer->image = $imageName;
            }

            $offer->meta_description = $this->meta_description;
            $offer->meta_keywords = $this->meta_keywords;
            $offer->meta_title = $this->meta_title;
            $offer->save();           
            return redirect()->route('offers');
        } else {
            $offer->title_ru = $this->title_ru;
            $offer->title_en = $this->title_en;
            $offer->title_kz = $this->title_kz;
            $offer->slug = $this->slug;        
            $dom = new DOMDocument();
            @$dom->loadHTML($offer->description_ru, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $descriptionImages = $dom->getElementsByTagName('img');

            $this->beforeUpdate = array();
            if($descriptionImages->length > 0)
            {          
                foreach ($descriptionImages as $img) {
                    $this->beforeUpdate[] = $img->getAttribute('src');                
                }      
            }
            
            $dom = new DomDocument();
            @$dom->loadHTML(mb_convert_encoding($this->description_ru, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $images = $dom->getElementsByTagName('img');
    

            $this->afterUpdate = array();
            if($images->length > 0)
            {
                foreach ($images as $key => $img) {
                    
                    $pattern = "/^\/upload\/offers\/.+$/";
                    $data = $img->getAttribute('src');                
                    
                    if(!preg_match($pattern, $data))
                    {

                        list($type, $data) = explode(';', $data);
                        list(, $data) = explode(',', $data);
                        $data = base64_decode($data);
                        list(, $type) = explode(':', $type);
                        list(, $type) = explode('/', $type); 
                        
                        $image_name = "/upload/offers/" . time(). $key . '.' . $type;
                        $path = public_path() . $image_name;
                        file_put_contents($path, $data);
                        
                        $img->removeAttribute('src');
                        $img->setAttribute('src', $image_name);
                        
                    } else {
                        $this->afterUpdate[] = $data;
                    }
                }

                $this->description_ru = $dom->saveHTML($dom->documentElement) . PHP_EOL . PHP_EOL;
            
            }
            
            foreach ($this->beforeUpdate as $path) {
                
                if(!in_array($path, $this->afterUpdate))
                {
                    $file = public_path($path);
                    
                    if(File::exists($file))
                    {
                        File::delete($file);
                    }
                }
                
            }     
            $offer->description_ru = $this->description_ru;
            $offer->description_en = $this->description_en;
            $offer->description_kz = $this->description_kz;
            $offer->status = $this->status;

            if($this->newimage){
                $imageName = $offer->image;
                $this->newimage->storeAs('offers', $imageName);
                $offer->image = $imageName;
            }

            $offer->meta_description = $this->meta_description;
            $offer->meta_keywords = $this->meta_keywords;
            $offer->meta_title = $this->meta_title;
            $validatedData = $this->validate();
            $offer->save($validatedData);           
            return redirect()->route('offers');
        }
    }


    public function render()
    {
        return view('livewire.edit-special-offer-component')->layout('layouts.admin');
    }
}
