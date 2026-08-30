<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategoriesComponent extends Component
{
    public $searchTerm = '';

    /** ID раскрытых веток дерева (по умолчанию всё свёрнуто). */
    public $expanded = [];

    public function toggleExpand($id)
    {
        $id = (int) $id;
        if (in_array($id, $this->expanded, true)) {
            $this->expanded = array_values(array_diff($this->expanded, [$id]));
        } else {
            $this->expanded[] = $id;
        }
    }

    public function toggleStatus($id)
    {
        $c = Category::find($id);
        if ($c) {
            $c->status = $c->status ? 0 : 1;
            $c->save();
            $this->dispatch('toast', message: 'Статус обновлён', type: 'success');
        }
    }

    public function delete($id)
    {
        $c = Category::find($id);
        if ($c) {
            Category::where('parent_id', $id)->update(['parent_id' => null]);
            $c->delete();
            $this->dispatch('toast', message: 'Категория удалена', type: 'success');
        }
    }

    /**
     * Drag-and-drop: переместить категорию к новому родителю и пересохранить
     * порядок «братьев». $parentId === '' | 'root' | null => корень.
     * $orderedIds — порядок ID в целевом списке после перетаскивания.
     */
    public function moveCategory($id, $parentId, $orderedIds = [])
    {
        $id = (int) $id;
        $cat = Category::find($id);
        if (! $cat) {
            return;
        }

        $newParent = ($parentId === '' || $parentId === 'root' || $parentId === null)
            ? null
            : (int) $parentId;

        // Защита: нельзя вложить категорию в саму себя или в своего потомка.
        if ($newParent !== null) {
            if ($newParent === $id || $this->isDescendant($newParent, $id)) {
                $this->dispatch('toast', message: 'Нельзя переместить категорию внутрь её же подкатегории', type: 'error');
                return;
            }
        }

        DB::transaction(function () use ($cat, $newParent, $orderedIds) {
            if ((int) $cat->parent_id !== (int) $newParent) {
                $cat->parent_id = $newParent;
                $cat->save();
            }
            $pos = 0;
            foreach ($orderedIds as $cid) {
                Category::where('id', (int) $cid)->update(['sort_order' => $pos++]);
            }
        });

        $this->dispatch('toast', message: 'Порядок категорий сохранён', type: 'success');

        // DOM уже переставлен SortableJS — не перерисовываем, чтобы не «прыгало».
        $this->skipRender();
    }

    /** Является ли $maybeChildId потомком $ancestorId. */
    protected function isDescendant($maybeChildId, $ancestorId): bool
    {
        $node = Category::find($maybeChildId);
        $guard = 0;
        while ($node && $node->parent_id && $guard++ < 100) {
            if ((int) $node->parent_id === (int) $ancestorId) {
                return true;
            }
            $node = Category::find($node->parent_id);
        }
        return false;
    }

    public function render()
    {
        $roots = Category::with(['children' => fn ($q) => $q->with('children')->orderBy('sort_order')])
            ->whereNull('parent_id')
            ->when($this->searchTerm, fn ($q) => $q->where('name', 'LIKE', '%' . $this->searchTerm . '%'))
            ->orderBy('sort_order')
            ->get();

        return view('livewire.categories-component', compact('roots'))
            ->layout('layouts.admin');
    }
}
