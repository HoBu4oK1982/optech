@section('title', 'Статьи — OPTECH')
@section('page-title', 'Статьи')
<div>
    <div class="ad-page-head">
        <div><h2>Статьи</h2></div>
        <a wire:navigate href="{{ route('addarticle') }}" class="ad-btn ad-btn-primary"><i class="fas fa-plus"></i> Новая статья</a>
    </div>
    <div class="ad-panel-shell">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:18px;flex-wrap:wrap;">
            <div class="ad-search"><i class="fas fa-search"></i><input type="text" wire:model.live.debounce.400ms="searchTerm" placeholder="Поиск по заголовку..."></div>
            <select wire:model.live="typeFilter" class="ad-field__select" style="max-width:220px;">
                <option value="">Все разделы</option>
                @foreach(\App\Models\Article::TYPES as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <div class="ad-counter">Всего: {{ $articles->total() }}</div>
        </div>
        <div class="ad-table-wrap">
            <table class="ad-table">
                <thead><tr><th style="width:64px;">Фото</th><th>Заголовок</th><th>Раздел</th><th>Дата</th><th>Статус</th><th style="text-align:right;">Действия</th></tr></thead>
                <tbody>
                @forelse($articles as $a)
                    <tr wire:key="art-{{ $a->id }}">
                        <td>
                            @php($aimg = $a->image
                                ? (file_exists(public_path('assets/images/articles/'.$a->image))
                                    ? asset('assets/images/articles/'.$a->image)
                                    : asset('assets/images/articles/'.$a->image))
                                : null)
                            @if($aimg)<img class="ad-thumb" src="{{ $aimg }}" alt="" onerror="this.style.display='none'">@else<div class="ad-thumb" style="display:grid;place-items:center;"><i class="fas fa-newspaper" style="opacity:.4;"></i></div>@endif
                        </td>
                        <td><div style="font-weight:700;">{{ $a->title_ru }}</div><div class="ad-counter">/{{ $a->slug }}</div></td>
                        <td><span class="ad-badge">{{ \App\Models\Article::TYPES[$a->type ?? \App\Models\Article::TYPE_ARTICLE] ?? 'Новость' }}</span></td>
                        <td>{{ optional($a->published_at)->format('d.m.Y') ?: '—' }}</td>
                        <td><button wire:click="toggleStatus({{ $a->id }})" class="ad-badge {{ (int)$a->status === 0 ? 'ad-badge--on' : 'ad-badge--off' }}" style="border:none;cursor:pointer;">{{ (int)$a->status === 0 ? 'Опубликована' : 'Черновик' }}</button></td>
                        <td><div class="ad-row-actions">
                            <a wire:navigate href="{{ route('editarticle', $a->slug) }}" class="ad-icon-btn"><i class="fas fa-pen"></i></a>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="ad-empty"><i class="fas fa-newspaper"></i>Статей пока нет.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:18px;">{{ $articles->links('pagination-admin') }}</div>
    </div>
</div>
