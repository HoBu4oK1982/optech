<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SpecialOffer;

class SpecialOffersComponent extends Component
{
    use WithPagination;
    public $searchTerm;

    public function render()
    {
        $search = '%' . $this->searchTerm . '%';
        $offers = SpecialOffer::where('title_ru', 'LIKE', $search)
        ->orderBy('id', 'DESC')
        ->paginate(10);
        return view('livewire.special-offers-component', [
            'offers' => $offers,
        ])->layout('layouts.admin');
    }
}
