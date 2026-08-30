@section('title', 'Бренды — OPTECH')
@section('page-title', 'Бренды')
<div>
    <div class="ad-page-head">
        <div>
            <h2>Бренды</h2>
        </div>
        <a wire:navigate href="{{ route('addbrand') }}" class="ad-btn ad-btn-primary"><i class="fas fa-plus"></i> Создать бренд</a>
    </div>

    <div class="ad-panel-shell">
        <div class="ad-search" style="max-width:440px;margin-bottom:18px;">
            <i class="fas fa-search"></i>
            <input type="text" wire:model.live.debounce.300ms="searchTerm" placeholder="Введите название бренда">
        </div>

        <style>
            .ad-drag{cursor:grab;color:var(--admin-muted);width:22px;display:inline-grid;place-items:center;}
            .ad-drag:active{cursor:grabbing;}
            .sortable-ghost{opacity:.45;background:rgba(59,130,246,.10);}
            .sortable-chosen{background:rgba(59,130,246,.06);}
        </style>

        <div class="ad-table-wrap">
            <table class="ad-table">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th style="width:60px;">Id</th>
                        <th>Заголовок</th>
                        <th>Транслит</th>
                        <th style="width:90px;">Логотип</th>
                        <th style="width:120px;">Статус</th>
                        <th style="width:96px;text-align:right;">Действия</th>
                    </tr>
                </thead>
                <tbody data-brand-sortable>
                    @forelse ($brands as $brand)
                        <tr wire:key="brand-{{ $brand->id }}" data-id="{{ $brand->id }}">
                            <td><span class="ad-drag" title="Перетащить для сортировки"><i class="fas fa-grip-vertical"></i></span></td>
                            <td>{{ $brand->id }}</td>
                            <td style="font-weight:600;">{{ $brand->name }}</td>
                            <td><span class="ad-counter">{{ $brand->slug }}</span></td>
                            <td>
                                @if($brand->image)
                                    <img class="ad-thumb" style="width:52px;height:34px;object-fit:contain;background:#fff;" src="{{ asset('assets/images/brands/'.$brand->image) }}" alt="" onerror="this.style.display='none'">
                                @endif
                            </td>
                            <td>
                                <button wire:click="toggleStatus({{ $brand->id }})" class="ad-badge {{ (int)$brand->status === 0 ? 'ad-badge--on' : 'ad-badge--off' }}" style="border:none;cursor:pointer;" title="Нажмите, чтобы переключить">
                                    {{ (int)$brand->status === 0 ? 'Включен' : 'Выключен' }}
                                </button>
                            </td>
                            <td>
                                <div class="ad-row-actions" style="justify-content:flex-end;">
                                    <a wire:navigate href="{{ route('editbrand', ['brand_slug' => $brand->slug]) }}" class="ad-icon-btn" title="Редактировать"><i class="fas fa-pen"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="ad-empty"><i class="fas fa-box-open"></i>Брендов пока нет.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    (function () {
        function initBrandSortable() {
            if (typeof Sortable === 'undefined') return;
            document.querySelectorAll('tbody[data-brand-sortable]').forEach(function (tb) {
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
                            window.Livewire.find(rootEl.getAttribute('wire:id')).call('moveBrand', ids);
                        }
                    }
                });
            });
        }
        document.addEventListener('DOMContentLoaded', initBrandSortable);
        document.addEventListener('livewire:navigated', initBrandSortable);
        document.addEventListener('livewire:init', function () {
            if (window.Livewire && Livewire.hook) {
                Livewire.hook('commit', function (payload) {
                    if (payload && typeof payload.succeed === 'function') {
                        payload.succeed(function () { setTimeout(initBrandSortable, 60); });
                    }
                });
            }
        });
        setTimeout(initBrandSortable, 300);
    })();
</script>
@endpush
