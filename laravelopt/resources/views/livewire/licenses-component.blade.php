@section('title', 'Лицензии — OPTECH')
@section('page-title', 'Лицензии')
<div>
    <div class="ad-page-head">
        <div><h2>Лицензии</h2></div>
        <a wire:navigate href="{{ route('addlicense') }}" class="ad-btn ad-btn-primary"><i class="fas fa-plus"></i> Добавить лицензию</a>
    </div>

    <style>
        .ad-drag{cursor:grab;color:var(--admin-muted);width:22px;display:inline-grid;place-items:center;}
        .ad-drag:active{cursor:grabbing;}
        .sortable-ghost{opacity:.45;background:rgba(59,130,246,.10);}
        .sortable-chosen{background:rgba(59,130,246,.06);}
        .sl-input{width:100%;padding:8px 10px;border:1px solid var(--admin-border);border-radius:10px;background:var(--admin-surface);color:var(--admin-text);}
    </style>

    <div class="ad-panel-shell">
        <div class="ad-table-wrap">
            <table class="ad-table">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th style="width:90px;">Изображение</th>
                        <th style="width:220px;">Тип</th>
                        <th>ALT</th>
                        <th style="width:150px;text-align:right;">Действия</th>
                    </tr>
                </thead>
                <tbody data-license-sortable>
                    @forelse ($licenses as $license)
                        <tr wire:key="lic-{{ $license->id }}" data-id="{{ $license->id }}">
                            <td><span class="ad-drag" title="Перетащить для сортировки"><i class="fas fa-grip-vertical"></i></span></td>
                            <td>
                                @if($license->image)
                                    <img class="ad-thumb" style="width:54px;height:54px;object-fit:cover;" src="{{ asset('assets/images/licenses/'.$license->image) }}" alt="{{ $license->alt }}" onerror="this.onerror=null;this.src='{{ asset('assets/images/licenses/'.$license->image) }}';">
                                @endif
                            </td>
                            <td>
                                @if ((int)$license->type === 1) Лицензия
                                @elseif ((int)$license->type === 2) Сертификат ИСО
                                @elseif ((int)$license->type === 3) Авторизационное письмо
                                @endif
                            </td>
                            <td><input type="text" class="sl-input" wire:model.blur="alts.{{ $license->id }}" placeholder="ALT"></td>
                            <td>
                                <div class="ad-row-actions" style="justify-content:flex-end;">
                                    <button wire:click="saveLicense({{ $license->id }})" class="ad-icon-btn" title="Сохранить"><i class="fas fa-check"></i></button>
                                    <button wire:click="deleteAttribute({{ $license->id }})" wire:confirm="Удалить лицензию?" class="ad-icon-btn danger" title="Удалить"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="ad-empty"><i class="fas fa-certificate"></i>Лицензий пока нет.</div></td></tr>
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
        function initLicenseSortable() {
            if (typeof Sortable === 'undefined') return;
            document.querySelectorAll('tbody[data-license-sortable]').forEach(function (tb) {
                if (tb.dataset.sortInit) return;
                tb.dataset.sortInit = '1';
                new Sortable(tb, {
                    handle: '.ad-drag', draggable: 'tr', animation: 150,
                    onEnd: function () {
                        var ids = Array.prototype.slice.call(tb.querySelectorAll('tr[data-id]')).map(function (tr) { return tr.dataset.id; });
                        var rootEl = tb.closest('[wire\\:id]');
                        if (rootEl && window.Livewire) window.Livewire.find(rootEl.getAttribute('wire:id')).call('moveLicense', ids);
                    }
                });
            });
        }
        document.addEventListener('DOMContentLoaded', initLicenseSortable);
        document.addEventListener('livewire:navigated', initLicenseSortable);
        document.addEventListener('livewire:init', function () {
            if (window.Livewire && Livewire.hook) {
                Livewire.hook('commit', function (payload) {
                    if (payload && typeof payload.succeed === 'function') {
                        payload.succeed(function () { setTimeout(function(){
                            document.querySelectorAll('tbody[data-license-sortable]').forEach(function(tb){ tb.dataset.sortInit=''; });
                            initLicenseSortable();
                        }, 60); });
                    }
                });
            }
        });
        setTimeout(initLicenseSortable, 300);
    })();
</script>
@endpush
