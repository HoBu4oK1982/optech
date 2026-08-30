<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Service;

class ServicesComponent extends Component
{
    use WithPagination;
    public $searchTerm;

    public function render()
    {
        $search = '%' . $this->searchTerm . '%';
        $services = Service::where('title_ru', 'LIKE', $search)
        ->orderBy('id', 'DESC')
        ->paginate(10);
        return view('livewire.services-component', [
            'services' => $services,
        ])->layout('layouts.admin');
    }
}
