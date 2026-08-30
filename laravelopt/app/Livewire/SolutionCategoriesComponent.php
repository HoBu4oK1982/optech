<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SolutionCategory;

class SolutionCategoriesComponent extends Component
{
    public $searchTerm = '';

    public function toggleStatus($id)
    {
        $c = SolutionCategory::find($id);
        if ($c) { $c->status = $c->status ? 0 : 1; $c->save(); $this->dispatch('toast', message: 'Статус обновлён', type: 'success'); }
    }

    public function delete($id)
    {
        $c = SolutionCategory::find($id);
        if ($c) { $c->delete(); $this->dispatch('toast', message: 'Удалено', type: 'success'); }
    }

    public function moveSolCategory($orderedIds = [])
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($orderedIds) {
            $pos = 0;
            foreach ($orderedIds as $id) {
                SolutionCategory::where('id', (int) $id)->update(['sort_order' => $pos++]);
            }
        });
        $this->dispatch('toast', message: 'Порядок сохранён', type: 'success');
        $this->skipRender();
    }

    public function render()
    {
        $items = SolutionCategory::where('title_ru', 'LIKE', '%' . $this->searchTerm . '%')->orderBy('sort_order')->get();
        return view('livewire.solution-categories-component', compact('items'))->layout('layouts.admin');
    }
}
