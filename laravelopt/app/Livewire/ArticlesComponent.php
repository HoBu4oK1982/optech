<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Article;

class ArticlesComponent extends Component
{
    use WithPagination;

    public $searchTerm = '';

    /** '' — все, article — новости, knowledge_base — База знаний. */
    public $typeFilter = '';

    public function updatingSearchTerm() { $this->resetPage(); }
    public function updatingTypeFilter() { $this->resetPage(); }

    public function toggleStatus($id)
    {
        $a = Article::find($id);
        if ($a) { $a->status = $a->status ? 0 : 1; $a->save(); $this->dispatch('toast', message: 'Статус обновлён', type: 'success'); }
    }

    public function delete($id)
    {
        $a = Article::find($id);
        if ($a) { $a->delete(); $this->dispatch('toast', message: 'Статья удалена', type: 'success'); }
    }

    public function render()
    {
        $articles = Article::where('title_ru', 'LIKE', '%' . $this->searchTerm . '%')
            ->when($this->typeFilter !== '', function ($q) {
                // У материалов, созданных до появления колонки type, там NULL —
                // показываем их вместе с новостями.
                $q->where('type', $this->typeFilter);
                if ($this->typeFilter === Article::TYPE_ARTICLE) {
                    $q->orWhereNull('type');
                }
            })
            ->orderBy('id', 'DESC')->paginate(12);
        return view('livewire.articles-component', compact('articles'))->layout('layouts.admin');
    }
}
