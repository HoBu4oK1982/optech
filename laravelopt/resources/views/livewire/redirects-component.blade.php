@section('title', 'Редиректы — OPTECH')
@section('page-title', 'Менеджер редиректов')

<div>
    <div class="ad-page-head">
        <div><h2>Редиректы 301/302</h2></div>
    </div>

    <div class="ad-form-layout">
        <div class="ad-form-main">
            <div class="ad-panel-shell">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:18px;flex-wrap:wrap;">
                    <div class="ad-search"><i class="fas fa-search"></i><input type="text" wire:model.live.debounce.400ms="searchTerm" placeholder="Поиск по URL..."></div>
                    <div class="ad-counter">Всего: {{ $redirects->total() }}</div>
                </div>
                <div class="ad-table-wrap">
                    <table class="ad-table">
                        <thead><tr><th>Откуда</th><th>Куда</th><th>Код</th><th>Хитов</th><th>Активен</th><th style="text-align:right;">Действия</th></tr></thead>
                        <tbody>
                        @forelse($redirects as $r)
                            <tr wire:key="rd-{{ $r->id }}">
                                <td style="font-family:monospace;font-size:.84rem;">{{ $r->from_url }}</td>
                                <td style="font-family:monospace;font-size:.84rem;">{{ $r->to_url }}</td>
                                <td><span class="ad-badge {{ $r->status_code == 301 ? 'ad-badge--on' : 'ad-badge--warn' }}">{{ $r->status_code }}</span></td>
                                <td>{{ $r->hits }}</td>
                                <td><button wire:click="toggleActive({{ $r->id }})" class="ad-badge {{ $r->is_active ? 'ad-badge--on' : 'ad-badge--off' }}" style="border:none;cursor:pointer;">{{ $r->is_active ? 'да' : 'нет' }}</button></td>
                                <td><div class="ad-row-actions">
                                    <button class="ad-icon-btn" wire:click="edit({{ $r->id }})"><i class="fas fa-pen"></i></button>
                                </div></td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="ad-empty"><i class="fas fa-route"></i>Редиректов пока нет.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:18px;">{{ $redirects->links('pagination-admin') }}</div>
            </div>
        </div>

        <div class="ad-form-side">
            <section class="ad-form-section">
                <div class="ad-section-head mb-0"><span class="ad-section-icon"><i class="fas fa-{{ $editingId ? 'pen' : 'plus' }}"></i></span><div><h3>{{ $editingId ? 'Редактировать' : 'Новый редирект' }}</h3></div></div>
                <form wire:submit="saveRedirect" class="ad-fields-grid" style="margin-top:16px;">
                    <label class="ad-field"><span>Откуда (from)</span><input type="text" wire:model="from_url" placeholder="/old-category/item">@error('from_url')<span class="ad-field-error">{{ $message }}</span>@enderror</label>
                    <label class="ad-field"><span>Куда (to)</span><input type="text" wire:model="to_url" placeholder="/catalog/item">@error('to_url')<span class="ad-field-error">{{ $message }}</span>@enderror</label>
                    <label class="ad-field"><span>Код</span><select wire:model="status_code"><option value="301">301 (постоянный)</option><option value="302">302 (временный)</option></select></label>
                    <label class="ad-check-field"><input type="checkbox" wire:model="is_active"><span>Активен</span></label>
                    <div style="display:flex;gap:10px;">
                        <button type="submit" class="ad-btn ad-btn-primary" style="flex:1;"><i class="fas fa-save"></i> {{ $editingId ? 'Обновить' : 'Добавить' }}</button>
                        @if($editingId)<button type="button" class="ad-btn" wire:click="resetForm">Отмена</button>@endif
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>
