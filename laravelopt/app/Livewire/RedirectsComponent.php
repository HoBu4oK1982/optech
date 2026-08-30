<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Redirect;

class RedirectsComponent extends Component
{
    use WithPagination;

    public $searchTerm = '';
    public $editingId = null;
    public $from_url, $to_url, $status_code = 301, $is_active = true;

    protected function rules()
    {
        return [
            'from_url' => 'required|string|max:2048|unique:redirects,from_url,' . ($this->editingId ?? 'NULL'),
            'to_url' => 'required|string|max:2048',
            'status_code' => 'required|in:301,302',
        ];
    }

    protected $messages = ['from_url.unique' => 'Редирект с этого URL уже существует.'];

    public function updatingSearchTerm() { $this->resetPage(); }

    public function resetForm()
    {
        $this->reset(['editingId', 'from_url', 'to_url']);
        $this->status_code = 301;
        $this->is_active = true;
    }

    public function edit($id)
    {
        $r = Redirect::findOrFail($id);
        $this->editingId = $r->id;
        $this->from_url = $r->from_url;
        $this->to_url = $r->to_url;
        $this->status_code = $r->status_code;
        $this->is_active = (bool) $r->is_active;
    }

    public function saveRedirect()
    {
        $this->validate();
        Redirect::updateOrCreate(
            ['id' => $this->editingId],
            [
                'from_url' => $this->from_url,
                'to_url' => $this->to_url,
                'status_code' => $this->status_code,
                'is_active' => (bool) $this->is_active,
            ]
        );
        $this->dispatch('toast', message: $this->editingId ? 'Редирект обновлён' : 'Редирект добавлен', type: 'success');
        $this->resetForm();
    }

    public function toggleActive($id)
    {
        $r = Redirect::find($id);
        if ($r) { $r->is_active = ! $r->is_active; $r->save(); }
    }

    public function delete($id)
    {
        Redirect::where('id', $id)->delete();
        $this->dispatch('toast', message: 'Редирект удалён', type: 'success');
        if ($this->editingId === $id) $this->resetForm();
    }

    public function render()
    {
        $redirects = Redirect::when($this->searchTerm, fn ($q) =>
                $q->where('from_url', 'LIKE', '%' . $this->searchTerm . '%')
                  ->orWhere('to_url', 'LIKE', '%' . $this->searchTerm . '%'))
            ->orderBy('id', 'DESC')->paginate(20);

        return view('livewire.redirects-component', compact('redirects'))->layout('layouts.admin');
    }
}
