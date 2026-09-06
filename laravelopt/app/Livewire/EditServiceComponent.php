<?php

namespace App\Livewire;

use Livewire\Component;
use App\Livewire\Concerns\HasSeoFields;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Carbon\Carbon;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use DOMDocument;
use Illuminate\Support\Facades\File;

class EditServiceComponent extends Component
{   
    use WithFileUploads;
    use HasSeoFields;
    public $title_ru;
    public $title_kz;
    public $title_en;
    public $description_ru;
    public $description_kz;
    public $description_en;
    public $slug;
    public $image;
    public $status;
    public $newimage;
    public $beforeUpdate;
    public $afterUpdate;
    public $service_id;

    public function mount($service_slug){
        $service = Service::where('slug', $service_slug)->first();
        $this->service_id = $service->id;
        $this->title_ru = $service->title_ru;
        $this->title_en = $service->title_en;
        $this->title_kz = $service->title_kz;
        $this->slug = $service->slug;
        $this->image = $service->image;        
        $this->description_ru = $service->description_ru;
        $this->description_en = $service->description_en;
        $this->description_kz = $service->description_kz;
        
        $this->status = $service->status;
        $this->loadSeoFields($service);
    }

    protected $rules = [
        'title_ru' => 'required',
        'slug' => 'required|unique:services',
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

    public function updateService(){
        $service = Service::find($this->service_id);
        if($this->slug === $service->slug){
            $service->title_ru = $this->title_ru;
            $service->title_en = $this->title_en;
            $service->title_kz = $this->title_kz;
            $service->slug = $this->slug;

            
            $dom = new DOMDocument();
            @$dom->loadHTML($service->description_ru, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
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
                    
                    $pattern = "/^\/upload\/services\/.+$/";
                    $data = $img->getAttribute('src');                
                    
                    if(!preg_match($pattern, $data))
                    {

                        list($type, $data) = explode(';', $data);
                        list(, $data) = explode(',', $data);
                        $data = base64_decode($data);
                        list(, $type) = explode(':', $type);
                        list(, $type) = explode('/', $type); 
                        
                        $image_name = "/upload/services/" . time(). $key . '.' . $type;
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
            


            $service->description_ru = $this->description_ru;
            $service->description_en = $this->description_en;
            $service->description_kz = $this->description_kz;
            $service->status = $this->status;

            if($this->newimage){
                $imageName = $service->image;
                $this->newimage->storeAs('services', $imageName);
                $service->image = $imageName;
            }

            $this->applySeoFields($service, 'services');
            $service->save();           
            return redirect()->route('services');
        }else{
            $service->title_ru = $this->title_ru;
            $service->title_en = $this->title_en;
            $service->title_kz = $this->title_kz;
            $service->slug = $this->slug;

            
            $dom = new DOMDocument();
            @$dom->loadHTML($service->description_ru, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
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
                    
                    $pattern = "/^\/upload\/services\/.+$/";
                    $data = $img->getAttribute('src');                
                    
                    if(!preg_match($pattern, $data))
                    {

                        list($type, $data) = explode(';', $data);
                        list(, $data) = explode(',', $data);
                        $data = base64_decode($data);
                        list(, $type) = explode(':', $type);
                        list(, $type) = explode('/', $type); 
                        
                        $image_name = "/upload/services/" . time(). $key . '.' . $type;
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
            


            $service->description_ru = $this->description_ru;
            $service->description_en = $this->description_en;
            $service->description_kz = $this->description_kz;
            $service->status = $this->status;

            if($this->newimage){
                $imageName = $service->image;
                $this->newimage->storeAs('services', $imageName);
                $service->image = $imageName;
            }

            $this->applySeoFields($service, 'services');
            $validatedData = $this->validate();
            $service->save($validatedData);           
            return redirect()->route('services');
        }
    }

    public function render()
    {
        return view('livewire.edit-service-component')->layout('layouts.admin');
    }
}
