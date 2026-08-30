@section('title', 'Спецпредложения — OPTECH')
@section('page-title', 'Спецпредложения')
<div>
    <div class="ad-page-head">
        <div>
            <h2>Специальные предложения</h2>
        </div>
        <a wire:navigate href="{{ route('addoffer') }}" class="ad-btn ad-btn-primary"><i class="fas fa-plus"></i> Создать предложение</a>
    </div>

    <div class="ad-panel-shell">
        <div class="ad-search" style="max-width:440px;margin-bottom:18px;">
            <i class="fas fa-search"></i>
            <input type="text" wire:model.live.debounce.300ms="searchTerm" placeholder="Введите название предложения">
        </div>

        <div class="ad-table-wrap">
            <table class="ad-table">
                <thead>
                    <tr>
                        <th style="width:60px;">Id</th>
                        <th>Заголовок</th>
                        <th>Транслит</th>
                        <th style="width:120px;">Статус</th>
                        <th style="width:96px;text-align:right;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($offers as $offer)
                        <tr>
                            <td>{{ $offer->id }}</td>
                            <td style="font-weight:600;">{{ $offer->title_ru }}</td>
                            <td><span class="ad-counter">{{ $offer->slug }}</span></td>
                            <td>
                                @if ((int)$offer->status === 0)
                                    <span class="ad-badge ad-badge--on">Включен</span>
                                @else
                                    <span class="ad-badge ad-badge--off">Выключен</span>
                                @endif
                            </td>
                            <td>
                                <div class="ad-row-actions" style="justify-content:flex-end;">
                                    <a wire:navigate href="{{ route('editoffer', ['offer_slug' => $offer->slug]) }}" class="ad-icon-btn" title="Редактировать"><i class="fas fa-pen"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="ad-empty"><i class="fas fa-percent"></i>Специальных предложений пока нет.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;">{{ $offers->links('pagination-links') }}</div>
    </div>
</div>
