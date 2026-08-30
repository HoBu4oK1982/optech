@section('title', 'Категории решений — OPTECH')
@section('page-title', 'Категории решений')
<div>
    <div class="ad-page-head"><div><h2>Категории решений</h2></div><a wire:navigate href="{{ route('addsolcategory') }}" class="ad-btn ad-btn-primary"><i class="fas fa-plus"></i> Новая категория</a></div>
    <div class="ad-panel-shell">
        <div style="margin-bottom:18px;"><div class="ad-search"><i class="fas fa-search"></i><input type="text" wire:model.live.debounce.400ms="searchTerm" placeholder="Поиск..."></div></div>

        <style>
            .ad-drag{cursor:grab;color:var(--admin-muted);width:22px;display:inline-grid;place-items:center;}
            .ad-drag:active{cursor:grabbing;}
            .sortable-ghost{opacity:.45;background:rgba(59,130,246,.10);}
            .sortable-chosen{background:rgba(59,130,246,.06);}
        </style>

        <div class="ad-table-wrap"><table class="ad-table">
            <thead><tr><th style="width:36px;"></th><th style="width:64px;">Фото</th><th>Название</th><th>Индексация</th><th>Статус</th><th style="text-align:right;">Действия</th></tr></thead>
            <tbody data-solcat-sortable>
            @forelse($items as $c)
                <tr wire:key="sc-{{ $c->id }}" data-id="{{ $c->id }}">
                    <td><span class="ad-drag" title="Перетащить для сортировки"><i class="fas fa-grip-vertical"></i></span></td>
                    <td>
                        @php($cimg = $c->image
                            ? (file_exists(public_path('assets/images/solcategories/'.$c->image))
                                ? asset('assets/images/solcategories/'.$c->image)
                                : asset('assets/images/solcategories/'.$c->image))
                            : null)
                        @if($cimg)<img class="ad-thumb" src="{{ $cimg }}" alt="" onerror="this.style.display='none'">@else<div class="ad-thumb" style="display:grid;place-items:center;"><i class="{{ $c->icon ?: 'fas fa-cubes' }}" style="opacity:.5;"></i></div>@endif
                    </td>
                    <td><div style="font-weight:700;">{{ $c->title_ru }}</div><div class="ad-counter">/{{ $c->slug }}</div></td>
                    <td>@if($c->is_indexable)<span class="ad-badge ad-badge--on">index</span>@else<span class="ad-badge ad-badge--off">noindex</span>@endif</td>
                    <td><button wire:click="toggleStatus({{ $c->id }})" class="ad-badge {{ (int)$c->status === 0 ? 'ad-badge--on' : 'ad-badge--off' }}" style="border:none;cursor:pointer;">{{ (int)$c->status === 0 ? 'Опубликована' : 'Скрыта' }}</button></td>
                    <td><div class="ad-row-actions"><a wire:navigate href="{{ route('editsolcategory', $c->slug) }}" class="ad-icon-btn"><i class="fas fa-pen"></i></a></div></td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="ad-empty"><i class="fas fa-cubes"></i>Пока нет категорий решений.</div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    (function () {
        function initSolcatSortable() {
            if (typeof Sortable === 'undefined') return;
            document.querySelectorAll('tbody[data-solcat-sortable]').forEach(function (tb) {
                if (tb.dataset.sortInit) return;
                tb.dataset.sortInit = '1';
                new Sortable(tb, {
                    handle: '.ad-drag',
                    draggable: 'tr',
                    animation: 150,
                    onEnd: function () {
                        var ids = Array.prototype.slice.call(tb.querySelectorAll('tr[data-id]'))
                            .map(function (tr) { return tr.dataset.id; });
                        var rootEl = tb.closest('[wire\\:id]');
                        if (rootEl && window.Livewire) {
                            window.Livewire.find(rootEl.getAttribute('wire:id')).call('moveSolCategory', ids);
                        }
                    }
                });
            });
        }
        document.addEventListener('DOMContentLoaded', initSolcatSortable);
        document.addEventListener('livewire:navigated', initSolcatSortable);
        document.addEventListener('livewire:init', function () {
            if (window.Livewire && Livewire.hook) {
                Livewire.hook('commit', function (payload) {
                    if (payload && typeof payload.succeed === 'function') {
                        payload.succeed(function () { setTimeout(initSolcatSortable, 60); });
                    }
                });
            }
        });
        setTimeout(initSolcatSortable, 300);
    })();
</script>
@endpush
