@section('title', 'Проекты — OPTECH')
@section('page-title', 'Проекты')
<div>
    <div class="ad-page-head"><div><h2>Проекты</h2></div><a wire:navigate href="{{ route('addproject') }}" class="ad-btn ad-btn-primary"><i class="fas fa-plus"></i> Новый проект</a></div>
    <div class="ad-panel-shell">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:18px;flex-wrap:wrap;"><div class="ad-search"><i class="fas fa-search"></i><input type="text" wire:model.live.debounce.400ms="searchTerm" placeholder="Поиск..."></div><div class="ad-counter">Всего: {{ $projects->total() }}</div></div>
        <div class="ad-table-wrap"><table class="ad-table">
            <thead><tr><th style="width:64px;">Фото</th><th>Название</th><th>Клиент</th><th>Год</th><th>Статус</th><th style="text-align:right;">Действия</th></tr></thead>
            <tbody>
            @forelse($projects as $p)
                <tr wire:key="prj-{{ $p->id }}">
                    <td>@if($p->image)<img class="ad-thumb" src="{{ asset('assets/images/projects/' . $p->image) }}" alt="" onerror="this.style.display='none'">@else<div class="ad-thumb" style="display:grid;place-items:center;"><i class="fas fa-briefcase" style="opacity:.4;"></i></div>@endif</td>
                    <td><div style="font-weight:700;">{{ $p->title_ru }}</div><div class="ad-counter">/{{ $p->slug }}</div></td>
                    <td>{{ $p->client ?: '—' }}</td>
                    <td>{{ $p->project_year ?: '—' }}</td>
                    <td><button wire:click="toggleStatus({{ $p->id }})" class="ad-badge {{ (int)$p->status === 0 ? 'ad-badge--on' : 'ad-badge--off' }}" style="border:none;cursor:pointer;">{{ (int)$p->status === 0 ? 'Опубликован' : 'Скрыт' }}</button></td>
                    <td><div class="ad-row-actions"><a wire:navigate href="{{ route('editproject', $p->slug) }}" class="ad-icon-btn"><i class="fas fa-pen"></i></a></div></td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="ad-empty"><i class="fas fa-briefcase"></i>Проектов пока нет.</div></td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div style="margin-top:18px;">{{ $projects->links('pagination-admin') }}</div>
    </div>
</div>
