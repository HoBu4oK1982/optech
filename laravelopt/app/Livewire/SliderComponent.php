<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Slider;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


class SliderComponent extends Component
{
    use WithFileUploads;

    public $sliders;
    public $newImage;
    public $imagePreview;
    public $newAlt = '';
    public $newLink = '';
    public $alts = [];
    public $links = [];

    public function mount()
    {
        $this->loadSliders();
    }

    public function loadSliders()
    {
        $this->sliders = Slider::orderBy('position')->get();
        foreach ($this->sliders as $s) {
            $this->alts[$s->id]  = $s->alt;
            $this->links[$s->id] = $s->link;
        }
    }

    public function updatedNewImage()
    {
        if ($this->newImage) {
            $this->imagePreview = $this->newImage->temporaryUrl();
        }
    }

    public function addSlider()
    {
        if (! $this->newImage) {
            $this->dispatch('toast', message: 'Выберите изображение слайда', type: 'error');
            return;
        }
        if (true) {
            $imageName = Carbon::now()->timestamp . '.' . $this->newImage->extension();
            $this->newImage->storeAs('sliders', $imageName, 'local');
            
            Slider::create([
                'image' => $imageName,
                'position' => $this->sliders->count() + 1,
                'alt' => $this->newAlt,
                'link' => $this->newLink,
            ]);
            
            $this->newImage = null;
            $this->imagePreview = null;
            $this->newAlt = '';
            $this->newLink = '';
            $this->loadSliders();
            $this->dispatch('toast', message: 'Слайд добавлен', type: 'success');
        }
    }

    public function getListeners()
    {
        return [
            'confirmDelete' => 'confirmDelete',
        ];
    }

    public function deleteSlider($id)
    {
        $this->dispatch('confirmDelete', id: $id);
    }

    public function confirmDelete($id)
    {
        $slider = Slider::findOrFail($id);
        Storage::delete('sliders/' . $slider->image);
        $slider->delete();
        $this->loadSliders();
    }

    public function moveSlide($orderedIds = [])
    {
        $pos = 1;
        foreach ($orderedIds as $id) {
            Slider::where('id', (int) $id)->update(['position' => $pos++]);
        }
        $this->loadSliders();
        $this->dispatch('toast', message: 'Порядок слайдов сохранён', type: 'success');
    }

    public function saveSlide($id)
    {
        Slider::where('id', (int) $id)->update([
            'alt'  => $this->alts[$id] ?? null,
            'link' => $this->links[$id] ?? null,
        ]);
        $this->dispatch('toast', message: 'Слайд сохранён', type: 'success');
    }

    public function updatePosition($id, $position)
    {
        Slider::where('id', $id)->update(['position' => $position]);
        $this->loadSliders();
    }


    public function render()
    {
        return view('livewire.slider-component')->layout('layouts.admin');
    }
}
