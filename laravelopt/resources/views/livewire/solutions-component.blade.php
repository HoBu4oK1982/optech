@section('title', 'Решения — OPTECH')
@section('page-title', 'Решения')
<div>
    <div class="ad-page-head"><div><h2>Решения</h2></div><a wire:navigate href="{{ route('addsolution') }}" class="ad-btn ad-btn-primary"><i class="fas fa-plus"></i> Новое решение</a></div>
    <div class="ad-panel-shell">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:18px;flex-wrap:wrap;"><div class="ad-search"><i class="fas fa-search"></i><input type="text" wire:model.live.debounce.400ms="searchTerm" placeholder="Поиск..."></div><div class="ad-counter">Всего: {{ $solutions->total() }}</div></div>
        <div class="ad-table-wrap"><table class="ad-table">
            <thead><tr><th style="width:64px;">Фото</th><th>Название</th><th>Категория</th><th>Статус</th><th style="text-align:right;">Действия</th></tr></thead>
            <tbody>
            @forelse($solutions as $s)
                <tr wire:key="sol-{{ $s->id }}">
                    <td>@if($s->image)<img class="ad-thumb" src="{{ asset('assets/images/solutions/' . $s->image) }}" alt="" onerror="this.style.display='none'">@else<div class="ad-thumb" style="display:grid;place-items:center;"><i class="fas fa-layer-group" style="opacity:.4;"></i></div>@endif</td>
                    <td><div style="font-weight:700;">{{ $s->title_ru }} @if($s->is_featured)<span class="ad-badge ad-badge--warn" style="margin-left:6px;">★</span>@endif</div><div class="ad-counter">/{{ $s->slug }}</div></td>
                    <td>{{ $s->category->title_ru ?? '—' }}</td>
                    <td><button wire:click="toggleStatus({{ $s->id }})" class="ad-badge {{ (int)$s->status === 0 ? 'ad-badge--on' : 'ad-badge--off' }}" style="border:none;cursor:pointer;">{{ (int)$s->status === 0 ? 'Опубликовано' : 'Скрыто' }}</button></td>
                    <td><div class="ad-row-actions"><a wire:navigate href="{{ route('editsolution', $s->slug) }}" class="ad-icon-btn"><i class="fas fa-pen"></i></a></div></td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="ad-empty"><i class="fas fa-layer-group"></i>Решений пока нет.</div></td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div style="margin-top:18px;">{{ $solutions->links('pagination-admin') }}</div>
    </div>
</div>
