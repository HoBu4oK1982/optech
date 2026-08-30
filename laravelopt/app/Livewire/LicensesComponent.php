<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\License;
use Illuminate\Support\Facades\Schema;

class LicensesComponent extends Component
{
    public $alts = [];

    public function mount()
    {
        $hasAlt = Schema::hasColumn('licenses', 'alt');
        foreach (License::all() as $l) {
            $this->alts[$l->id] = $hasAlt ? $l->alt : null;
        }
    }

    public function moveLicense($orderedIds = [])
    {
        if (! Schema::hasColumn('licenses', 'position')) {
            $this->dispatch('toast', message: 'Сначала выполните миграцию (нет колонки position)', type: 'error');
            return;
        }
        $pos = 1;
        foreach ($orderedIds as $id) {
            License::where('id', (int) $id)->update(['position' => $pos++]);
        }
        $this->dispatch('toast', message: 'Порядок лицензий сохранён', type: 'success');
    }

    public function saveLicense($id)
    {
        if (! Schema::hasColumn('licenses', 'alt')) {
            $this->dispatch('toast', message: 'Сначала выполните миграцию (нет колонки alt)', type: 'error');
            return;
        }
        License::where('id', (int) $id)->update(['alt' => $this->alts[$id] ?? null]);
        $this->dispatch('toast', message: 'Лицензия сохранена', type: 'success');
    }

    public function deleteAttribute($license_id)
    {
        $license = License::find($license_id);
        if ($license) {
            $license->delete();
            $this->dispatch('toast', message: 'Лицензия удалена', type: 'success');
        }
    }

    public function render()
    {
        $query = License::query();
        if (Schema::hasColumn('licenses', 'position')) {
            $query->orderBy('position', 'ASC')->orderBy('id', 'DESC');
        } else {
            $query->orderBy('id', 'DESC');
        }
        $licenses = $query->get();

        return view('livewire.licenses-component', [
            'licenses' => $licenses,
        ])->layout('layouts.admin');
    }
}
