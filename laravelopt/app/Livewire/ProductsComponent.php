<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\ProductTranslate;

class ProductsComponent extends Component
{
    use WithPagination;

    public $searchTerm = '';

    public function updatingSearchTerm()
    {
        $this->resetPage();
    }

    public function toggleStatus($id)
    {
        $product = Product::find($id);
        if ($product) {
            $product->status = $product->status ? 0 : 1;
            $product->save();
            $this->dispatch('toast', message: 'Статус обновлён', type: 'success');
        }
    }

    public function delete($id)
    {
        $product = Product::find($id);
        if ($product) {
            ProductTranslate::where('product_id', $id)->delete();
            $product->delete();
            $this->dispatch('toast', message: 'Товар удалён', type: 'success');
        }
    }

    public function render()
    {
        $search = '%' . $this->searchTerm . '%';
        $products = Product::with('category', 'brand')
            ->where('name', 'LIKE', $search)
            ->orderBy('id', 'DESC')
            ->paginate(12);

        return view('livewire.products-component', compact('products'))
            ->layout('layouts.admin');
    }
}
