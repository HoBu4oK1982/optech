<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Carbon\Carbon;
use App\Models\License;

class AddLicenseComponent extends Component
{
    use WithFileUploads;

    public $type;
    public $image;
    public $alt;

    protected $rules = [
        'type'  => 'required|not_in:0',
        'image' => 'required|image|max:8192',
    ];

    protected $messages = [
        'type.required'  => 'Выберите тип лицензии',
        'type.not_in'    => 'Выберите тип лицензии',
        'image.required' => 'Загрузите изображение',
        'image.image'    => 'Файл должен быть изображением',
        'image.max'      => 'Изображение слишком большое (макс. 8 МБ)',
    ];

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function addSolution()
    {
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('toast', message: $e->validator->errors()->first(), type: 'error');
            throw $e;
        }

        $license = new License();
        $license->type = $this->type;
        $license->alt = $this->alt;
        $imageName = Carbon::now()->timestamp . '.' . $this->image->extension();
        $this->image->storeAs('licenses', $imageName);
        $license->image = $imageName;
        $license->save();

        return redirect()->route('licenses');
    }

    public function render()
    {
        return view('livewire.add-license-component')->layout('layouts.admin');
    }
}
