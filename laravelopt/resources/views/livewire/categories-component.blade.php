@section('title', 'Категории — OPTECH')
@section('page-title', 'Категории')

<div>
    <div class="ad-page-head">
        <div>
            <h2>Категории</h2>
        </div>
        <a wire:navigate href="{{ route('addcategory') }}" class="ad-btn ad-btn-primary"><i class="fas fa-plus"></i> Новая категория</a>
    </div>

    <div class="ad-panel-shell">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:18px;flex-wrap:wrap;">
            <div class="ad-search">
                <i class="fas fa-search"></i>
                <input type="text" wire:model.live.debounce.400ms="searchTerm" placeholder="Поиск по названию...">
            </div>
        </div>

        <style>
            .ad-tree{list-style:none;margin:0;padding:0;}
            .ad-tree.ad-subtree{padding-left:26px;}
            .ad-node{margin:0;}
            .ad-node-row{display:flex;align-items:center;gap:10px;padding:8px 6px;border-bottom:1px solid var(--admin-border);}
            .ad-node-main{display:flex;align-items:center;gap:10px;flex:1 1 auto;min-width:0;}
            .ad-node-titles{min-width:0;}
            .ad-node-name{font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
            .ad-node-col{flex:0 0 auto;text-align:left;}
            .ad-col-products{width:80px;}
            .ad-col-index{width:110px;}
            .ad-col-status{width:150px;}
            .ad-col-actions{width:96px;display:flex;justify-content:flex-end;}
            .ad-tree-head{display:flex;align-items:center;gap:10px;padding:0 6px 10px;color:var(--admin-muted);font-size:.72rem;text-transform:uppercase;letter-spacing:.12em;font-weight:800;border-bottom:1px solid var(--admin-border);margin-bottom:4px;}
            .ad-tree-head .ad-th-cat{flex:1 1 auto;}
            .ad-drag{cursor:grab;color:var(--admin-muted);width:20px;display:grid;place-items:center;flex:0 0 20px;}
            .ad-drag:active{cursor:grabbing;}
            .sortable-ghost{opacity:.45;background:rgba(59,130,246,.10);border-radius:10px;}
            .sortable-chosen{background:rgba(59,130,246,.06);border-radius:10px;}
            .ad-subtree:empty{min-height:6px;}
            @media (max-width:860px){.ad-col-index,.ad-col-products{display:none;}.ad-col-status{width:auto;}}
        </style>

        <div class="ad-table-wrap">
            <div class="ad-tree-head">
                <span class="ad-th-cat" style="padding-left:30px;">Категория</span>
                <span class="ad-node-col ad-col-products">Товаров</span>
                <span class="ad-node-col ad-col-index">Индексация</span>
                <span class="ad-node-col ad-col-status">Статус</span>
                <span class="ad-node-col ad-col-actions">Действия</span>
            </div>

            @if($roots->isEmpty())
                <div class="ad-empty"><i class="fas fa-folder-open"></i>Категорий пока нет.</div>
            @else
                <ul class="ad-tree" data-sortable data-parent="root">
                    @foreach($roots as $category)
                        @include('livewire.partials._category-row', ['category' => $category, 'depth' => 0, 'expanded' => $expanded])
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    (function () {
        function initOne(ul) {
            if (typeof Sortable === 'undefined') return;
            if (ul.dataset.sortInit) return;
            ul.dataset.sortInit = '1';

            new Sortable(ul, {
                group: 'cats',
                handle: '.ad-drag',
                draggable: '.ad-node',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                onEnd: function (evt) {
                    var to = evt.to;
                    var ids = Array.prototype.slice.call(to.children)
                        .filter(function (li) { return li.dataset && li.dataset.id; })
                        .map(function (li) { return li.dataset.id; });
                    var movedId = evt.item.dataset.id;
                    var parent = to.dataset.parent || 'root';
                    var rootEl = to.closest('[wire\\:id]');
                    if (rootEl && window.Livewire) {
                        window.Livewire.find(rootEl.getAttribute('wire:id'))
                            .call('moveCategory', movedId, parent, ids);
                    }
                }
            });

            // Изоляция уровней: тап по ручке внутри вложенного списка не должен
            // всплывать к родительскому Sortable, иначе тянулась бы вся ветка.
            // stopPropagation не мешает Sortable этого же ul (он на том же элементе).
            ['pointerdown', 'mousedown', 'touchstart'].forEach(function (evName) {
                ul.addEventListener(evName, function (e) {
                    if (e.target.closest('.ad-drag')) {
                        e.stopPropagation();
                    }
                });
            });
        }

        function initAll(root) {
            (root || document).querySelectorAll('ul[data-sortable]').forEach(initOne);
        }

        /* Ключевой фикс: вложенные <ul data-sortable> появляются асинхронно после
           toggleExpand (Livewire round-trip). Вместо хрупкого setTimeout ловим их
           появление MutationObserver'ом и инициализируем сразу, независимо от
           скорости морфинга DOM (важно на боевом сервере за nginx). */
        var observer = null;
        function startObserver() {
            if (observer) return;
            var host = document.querySelector('.ad-panel-shell') || document.body;
            observer = new MutationObserver(function (mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    var added = mutations[i].addedNodes;
                    for (var j = 0; j < added.length; j++) {
                        var n = added[j];
                        if (n.nodeType !== 1) continue;
                        if (n.matches && n.matches('ul[data-sortable]')) initOne(n);
                        if (n.querySelectorAll) initAll(n);
                    }
                }
            });
            observer.observe(host, { childList: true, subtree: true });
        }

        function boot() {
            // работаем только там, где есть дерево категорий
            if (!document.querySelector('ul[data-sortable]') && !document.querySelector('.ad-tree')) return;
            initAll();
            startObserver();
        }

        document.addEventListener('DOMContentLoaded', boot);
        document.addEventListener('livewire:navigated', function () {
            // после SPA-навигации host — новый DOM: пересоздаём наблюдатель
            if (observer) { observer.disconnect(); observer = null; }
            boot();
        });
        // если скрипт исполнился уже после готовности DOM
        if (document.readyState !== 'loading') boot();
    })();
</script>
@endpush