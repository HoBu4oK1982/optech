<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Brand;
use Illuminate\Support\Facades\DB;

class BrandsComponent extends Component
{
    public $searchTerm;

    public function moveBrand($orderedIds = [])
    {
        DB::transaction(function () use ($orderedIds) {
            $pos = 0;
            foreach ($orderedIds as $id) {
                Brand::where('id', (int) $id)->update(['sort_order' => $pos++]);
            }
        });
        $this->dispatch('toast', message: 'Порядок брендов сохранён', type: 'success');
        $this->skipRender();
    }

    public function toggleStatus($id)
    {
        $brand = \App\Models\Brand::find($id);
        if ($brand) {
            $brand->status = (int) $brand->status === 0 ? 1 : 0;
            $brand->save();
            $this->dispatch('toast', message: 'Статус обновлён', type: 'success');
        }
    }

    public function render()
    {
        $search = '%' . $this->searchTerm . '%';
        $brands = Brand::where('name', 'LIKE', $search)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'DESC')
            ->get();

        return view('livewire.brands-component', compact('brands'))
            ->layout('layouts.admin');
    }
}
