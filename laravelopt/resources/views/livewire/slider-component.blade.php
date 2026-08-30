@section('title', 'Слайдеры — OPTECH')
@section('page-title', 'Слайдеры')
<div>
    <div class="ad-page-head"><div><h2>Слайдеры</h2></div></div>

    <style>
        .ad-drag{cursor:grab;color:var(--admin-muted);width:22px;display:inline-grid;place-items:center;}
        .ad-drag:active{cursor:grabbing;}
        .sortable-ghost{opacity:.45;background:rgba(59,130,246,.10);}
        .sortable-chosen{background:rgba(59,130,246,.06);}
        .sl-input{width:100%;padding:8px 10px;border:1px solid var(--admin-border);border-radius:10px;background:var(--admin-surface);color:var(--admin-text);}
    </style>

    <div class="ad-panel-shell" style="margin-bottom:18px;">
        <h3 style="margin:0 0 14px;font-size:1rem;">Добавить новый слайд</h3>
        <div class="ad-form-grid" style="max-width:640px;">
            <div class="ad-field"><span>Изображение</span>
                <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;">
                    <label class="ad-file" x-data="{n:''}"><i class="fas fa-upload"></i><span x-text="n || 'Выбрать изображение'"></span><input type="file" wire:model="newImage" x-on:change="n = ($event.target.files[0] && $event.target.files[0].name) || ''"></label>
                    <span class="ad-counter">Размер 2040×845 px</span>
                    <span wire:loading wire:target="newImage" class="ad-counter">Загрузка…</span>
                </div>
                @if ($imagePreview)<div style="margin-top:10px;"><img src="{{ $imagePreview }}" alt="Предпросмотр" class="ad-thumb" style="width:220px;height:auto;object-fit:contain;background:#fff;"></div>@endif
            </div>
            <label class="ad-field"><span>ALT (описание изображения)</span><input type="text" wire:model="newAlt" placeholder="Напр.: Оптическое оборудование OPTECH"></label>
            <label class="ad-field"><span>Ссылка (куда ведёт слайд)</span><input type="text" wire:model="newLink" placeholder="https://optech.kz/... или /catalog"></label>
        </div>
        <div style="margin-top:16px;"><button class="ad-btn ad-btn-primary" wire:click="addSlider"><i class="fas fa-plus"></i> Добавить</button></div>
    </div>

    <div class="ad-panel-shell">
        <h3 style="margin:0 0 14px;font-size:1rem;">Список слайдов</h3>
        <div class="ad-table-wrap">
            <table class="ad-table">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th style="width:200px;">Слайд</th>
                        <th>ALT</th>
                        <th>Ссылка</th>
                        <th style="width:150px;text-align:right;">Действия</th>
                    </tr>
                </thead>
                <tbody data-slider-sortable>
                    @forelse ($sliders as $slider)
                        <tr wire:key="slide-{{ $slider->id }}" data-id="{{ $slider->id }}">
                            <td><span class="ad-drag" title="Перетащить для сортировки"><i class="fas fa-grip-vertical"></i></span></td>
                            <td><img class="ad-thumb" style="width:180px;height:auto;object-fit:contain;background:#fff;" src="{{ asset('assets/images/sliders/'.$slider->image) }}" alt="{{ $slider->alt }}" onerror="this.onerror=null;this.src='{{ asset('assets/images/sliders/'.$slider->image) }}';"></td>
                            <td><input type="text" class="sl-input" wire:model.blur="alts.{{ $slider->id }}" placeholder="ALT"></td>
                            <td><input type="text" class="sl-input" wire:model.blur="links.{{ $slider->id }}" placeholder="Ссылка"></td>
                            <td>
                                <div class="ad-row-actions" style="justify-content:flex-end;">
                                    <button wire:click="saveSlide({{ $slider->id }})" class="ad-icon-btn" title="Сохранить"><i class="fas fa-check"></i></button>
                                    <button wire:click="confirmDelete({{ $slider->id }})" wire:confirm="Удалить слайд?" class="ad-icon-btn danger" title="Удалить"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="ad-empty"><i class="fas fa-images"></i>Слайдов пока нет.</div></td></tr>
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
        function initSliderSortable() {
            if (typeof Sortable === 'undefined') return;
            document.querySelectorAll('tbody[data-slider-sortable]').forEach(function (tb) {
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
                            window.Livewire.find(rootEl.getAttribute('wire:id')).call('moveSlide', ids);
                        }
                    }
                });
            });
        }
        document.addEventListener('DOMContentLoaded', initSliderSortable);
        document.addEventListener('livewire:navigated', initSliderSortable);
        document.addEventListener('livewire:init', function () {
            if (window.Livewire && Livewire.hook) {
                Livewire.hook('commit', function (payload) {
                    if (payload && typeof payload.succeed === 'function') {
                        payload.succeed(function () { setTimeout(function(){
                            document.querySelectorAll('tbody[data-slider-sortable]').forEach(function(tb){ tb.dataset.sortInit=''; });
                            initSliderSortable();
                        }, 60); });
                    }
                });
            }
        });
        setTimeout(initSliderSortable, 300);
    })();
</script>
@endpush
