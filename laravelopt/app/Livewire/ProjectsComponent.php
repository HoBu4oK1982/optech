<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Project;

class ProjectsComponent extends Component
{
    use WithPagination;
    public $searchTerm = '';
    public function updatingSearchTerm() { $this->resetPage(); }

    public function toggleStatus($id)
    {
        $p = Project::find($id);
        if ($p) { $p->status = $p->status ? 0 : 1; $p->save(); $this->dispatch('toast', message: 'Статус обновлён', type: 'success'); }
    }

    public function delete($id)
    {
        $p = Project::find($id);
        if ($p) { $p->delete(); $this->dispatch('toast', message: 'Удалено', type: 'success'); }
    }

    public function render()
    {
        $projects = Project::where('title_ru', 'LIKE', '%' . $this->searchTerm . '%')->orderBy('sort_order')->paginate(12);
        return view('livewire.projects-component', compact('projects'))->layout('layouts.admin');
    }
}
