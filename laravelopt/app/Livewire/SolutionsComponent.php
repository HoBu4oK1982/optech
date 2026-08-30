<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Solution;

class SolutionsComponent extends Component
{
    use WithPagination;
    public $searchTerm = '';
    public function updatingSearchTerm() { $this->resetPage(); }

    public function toggleStatus($id)
    {
        $s = Solution::find($id);
        if ($s) { $s->status = $s->status ? 0 : 1; $s->save(); $this->dispatch('toast', message: 'Статус обновлён', type: 'success'); }
    }

    public function delete($id)
    {
        $s = Solution::find($id);
        if ($s) { $s->delete(); $this->dispatch('toast', message: 'Удалено', type: 'success'); }
    }

    public function render()
    {
        $solutions = Solution::with('category')->where('title_ru', 'LIKE', '%' . $this->searchTerm . '%')->orderBy('sort_order')->paginate(12);
        return view('livewire.solutions-component', compact('solutions'))->layout('layouts.admin');
    }
}
