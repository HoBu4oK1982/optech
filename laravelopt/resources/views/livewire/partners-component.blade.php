@section('title', 'Партнёры — OPTECH')
@section('page-title', 'Партнёры')
<div>
    <div class="ad-page-head"><div><h2>Партнёры</h2></div></div>

    <style>
        .ad-drag{cursor:grab;color:var(--admin-muted);width:22px;display:inline-grid;place-items:center;}
        .ad-drag:active{cursor:grabbing;}
        .sortable-ghost{opacity:.45;background:rgba(59,130,246,.10);}
        .sortable-chosen{background:rgba(59,130,246,.06);}
        .sl-input{width:100%;padding:8px 10px;border:1px solid var(--admin-border);border-radius:10px;background:var(--admin-surface);color:var(--admin-text);}
    </style>

    <div class="ad-panel-shell" style="margin-bottom:18px;">
        <h3 style="margin:0 0 14px;font-size:1rem;">Добавить нового партнёра</h3>
        <div class="ad-form-grid" style="max-width:640px;">
            <div class="ad-field"><span>Логотип</span>
                <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;">
                    <label class="ad-file" x-data="{n:''}"><i class="fas fa-upload"></i><span x-text="n || 'Выбрать изображение'"></span><input type="file" wire:model="newImage" x-on:change="n = ($event.target.files[0] && $event.target.files[0].name) || ''"></label>
                    <span class="ad-counter">Размер 260×95 px</span>
                    <span wire:loading wire:target="newImage" class="ad-counter">Загрузка…</span>
                </div>
                @if ($imagePreview)<div style="margin-top:10px;"><img src="{{ $imagePreview }}" alt="Предпросмотр" class="ad-thumb" style="width:140px;height:auto;object-fit:contain;background:#fff;"></div>@endif
            </div>
            <label class="ad-field"><span>ALT (описание логотипа)</span><input type="text" wire:model="newAlt" placeholder="Напр.: Логотип Mikrotik"></label>
        </div>
        <div style="margin-top:16px;"><button class="ad-btn ad-btn-primary" wire:click="addPartner"><i class="fas fa-plus"></i> Добавить</button></div>
    </div>

    <div class="ad-panel-shell">
        <h3 style="margin:0 0 14px;font-size:1rem;">Список партнёров</h3>
        <div class="ad-table-wrap">
            <table class="ad-table">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th style="width:150px;">Логотип</th>
                        <th>ALT</th>
                        <th style="width:150px;text-align:right;">Действия</th>
                    </tr>
                </thead>
                <tbody data-partner-sortable>
                    @forelse ($partners as $partner)
                        <tr wire:key="partner-{{ $partner->id }}" data-id="{{ $partner->id }}">
                            <td><span class="ad-drag" title="Перетащить для сортировки"><i class="fas fa-grip-vertical"></i></span></td>
                            <td><img class="ad-thumb" style="width:120px;height:auto;object-fit:contain;background:#fff;" src="{{ asset('assets/images/partners/'.$partner->image) }}" alt="{{ $partner->alt }}" onerror="this.onerror=null;this.src='{{ asset('assets/images/partners/'.$partner->image) }}';"></td>
                            <td><input type="text" class="sl-input" wire:model.blur="alts.{{ $partner->id }}" placeholder="ALT"></td>
                            <td>
                                <div class="ad-row-actions" style="justify-content:flex-end;">
                                    <button wire:click="savePartner({{ $partner->id }})" class="ad-icon-btn" title="Сохранить"><i class="fas fa-check"></i></button>
                                    <button wire:click="confirmDelete({{ $partner->id }})" wire:confirm="Удалить партнёра?" class="ad-icon-btn danger" title="Удалить"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="ad-empty"><i class="fas fa-handshake"></i>Партнёров пока нет.</div></td></tr>
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
        function initPartnerSortable() {
            if (typeof Sortable === 'undefined') return;
            document.querySelectorAll('tbody[data-partner-sortable]').forEach(function (tb) {
                if (tb.dataset.sortInit) return;
                tb.dataset.sortInit = '1';
                new Sortable(tb, {
                    handle: '.ad-drag', draggable: 'tr', animation: 150,
                    onEnd: function () {
                        var ids = Array.prototype.slice.call(tb.querySelectorAll('tr[data-id]')).map(function (tr) { return tr.dataset.id; });
                        var rootEl = tb.closest('[wire\\:id]');
                        if (rootEl && window.Livewire) window.Livewire.find(rootEl.getAttribute('wire:id')).call('movePartner', ids);
                    }
                });
            });
        }
        document.addEventListener('DOMContentLoaded', initPartnerSortable);
        document.addEventListener('livewire:navigated', initPartnerSortable);
        document.addEventListener('livewire:init', function () {
            if (window.Livewire && Livewire.hook) {
                Livewire.hook('commit', function (payload) {
                    if (payload && typeof payload.succeed === 'function') {
                        payload.succeed(function () { setTimeout(function(){
                            document.querySelectorAll('tbody[data-partner-sortable]').forEach(function(tb){ tb.dataset.sortInit=''; });
                            initPartnerSortable();
                        }, 60); });
                    }
                });
            }
        });
        setTimeout(initPartnerSortable, 300);
    })();
</script>
@endpush
