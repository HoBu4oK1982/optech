{{-- Узел дерева (li). Параметры: $category, $depth, $expanded --}}
@php($depth = $depth ?? 0)
@php($expanded = $expanded ?? [])
@php($hasChildren = $category->children && count($category->children) > 0)
@php($isOpen = in_array($category->id, $expanded))
@php(
    $catImg = $category->image
        ? (file_exists(public_path('assets/images/categories/' . $category->image))
            ? asset('assets/images/categories/' . $category->image)
            : asset('assets/images/categories/' . $category->image))
        : null
)
<li class="ad-node" wire:key="cat-{{ $category->id }}" data-id="{{ $category->id }}">
    <div class="ad-node-row">
        <div class="ad-node-main">
            <span class="ad-drag" title="Перетащить для сортировки"><i class="fas fa-grip-vertical"></i></span>
            @if($hasChildren)
                <button type="button" wire:click="toggleExpand({{ $category->id }})" class="ad-tree-toggle" title="{{ $isOpen ? 'Свернуть' : 'Развернуть' }}">
                    <i class="fas fa-{{ $isOpen ? 'minus' : 'plus' }}"></i>
                </button>
            @else
                <span class="ad-tree-toggle ad-tree-toggle--empty"></span>
            @endif
            @if($catImg)
                <img class="ad-thumb" style="width:36px;height:36px;object-fit:cover;flex:0 0 36px;" src="{{ $catImg }}" alt="" onerror="this.style.display='none'">
            @else
                <div class="ad-thumb" style="width:36px;height:36px;display:grid;place-items:center;flex:0 0 36px;"><i class="{{ $category->icon ?: 'fas fa-folder' }}" style="opacity:.5;"></i></div>
            @endif
            <div class="ad-node-titles">
                <div class="ad-node-name">{{ $category->name }}</div>
                <div class="ad-counter">/{{ $category->slug }}</div>
            </div>
        </div>
        <div class="ad-node-col ad-col-products">{{ $category->product()->count() }}</div>
        <div class="ad-node-col ad-col-index">
            @if($category->is_indexable)
                <span class="ad-badge ad-badge--on">index</span>
            @else
                <span class="ad-badge ad-badge--off">noindex</span>
            @endif
        </div>
        <div class="ad-node-col ad-col-status">
            <button wire:click="toggleStatus({{ $category->id }})" class="ad-badge {{ (int)$category->status === 0 ? 'ad-badge--on' : 'ad-badge--off' }}" style="border:none;cursor:pointer;">
                {{ (int)$category->status === 0 ? 'Опубликована' : 'Скрыта' }}
            </button>
        </div>
        <div class="ad-node-col ad-col-actions">
            <div class="ad-row-actions">
                <a wire:navigate href="{{ route('editcategory', $category->slug) }}" class="ad-icon-btn" title="Редактировать"><i class="fas fa-pen"></i></a>
            </div>
        </div>
    </div>
    @if($hasChildren && $isOpen)
        <ul class="ad-tree ad-subtree" data-sortable data-parent="{{ $category->id }}">
            @foreach($category->children as $child)
                @include('livewire.partials._category-row', ['category' => $child, 'depth' => $depth + 1, 'expanded' => $expanded])
            @endforeach
        </ul>
    @endif
</li>
