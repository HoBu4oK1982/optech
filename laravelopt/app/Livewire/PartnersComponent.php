<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Partner;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PartnersComponent extends Component
{
    use WithFileUploads;

    public $partners;
    public $newImage;
    public $imagePreview;
    public $newAlt = '';
    public $alts = [];

    public function mount()
    {
        $this->loadPartners();
    }

    public function loadPartners()
    {
        $this->partners = Partner::orderBy('position')->get();
        foreach ($this->partners as $p) { $this->alts[$p->id] = $p->alt; }
    }

    public function updatedNewImage()
    {
        if ($this->newImage) {
            $this->imagePreview = $this->newImage->temporaryUrl();
        }
    }

    public function addPartner()
    {
        if (! $this->newImage) {
            $this->dispatch('toast', message: 'Выберите изображение партнёра', type: 'error');
            return;
        }
        $imageName = Carbon::now()->timestamp . '.' . $this->newImage->extension();
        $this->newImage->storeAs('partners', $imageName, 'local');
        Partner::create([
            'image' => $imageName,
            'position' => $this->partners->count() + 1,
            'alt' => $this->newAlt,
        ]);
        $this->newImage = null;
        $this->imagePreview = null;
        $this->newAlt = '';
        $this->loadPartners();
        $this->dispatch('toast', message: 'Партнёр добавлен', type: 'success');
    }

    public function getListeners()
    {
        return [
            'confirmDelete' => 'confirmDelete',
        ];
    }

    public function deletePartner($id)
    {
        $this->dispatch('confirmDelete', id: $id);
    }

    public function confirmDelete($id)
    {
        $partner = Partner::findOrFail($id);
        Storage::delete('partners/' . $partner->image);
        $partner->delete();
        $this->loadPartners();
    }

    public function movePartner($orderedIds = [])
    {
        $pos = 1;
        foreach ($orderedIds as $id) { Partner::where('id', (int) $id)->update(['position' => $pos++]); }
        $this->loadPartners();
        $this->dispatch('toast', message: 'Порядок партнёров сохранён', type: 'success');
    }

    public function savePartner($id)
    {
        Partner::where('id', (int) $id)->update(['alt' => $this->alts[$id] ?? null]);
        $this->dispatch('toast', message: 'Партнёр сохранён', type: 'success');
    }

    public function updatePosition($id, $position)
    {
        Partner::where('id', $id)->update(['position' => $position]);
        $this->loadPartners();
    }

    public function render()
    {
        return view('livewire.partners-component')->layout('layouts.admin');
    }
}