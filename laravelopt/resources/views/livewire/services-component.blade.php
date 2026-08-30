@section('title', 'Услуги — OPTECH')
@section('page-title', 'Услуги')
<div>
    <div class="ad-page-head">
        <div>
            <h2>Услуги</h2>
        </div>
        <a wire:navigate href="{{ route('addservice') }}" class="ad-btn ad-btn-primary"><i class="fas fa-plus"></i> Создать услугу</a>
    </div>

    <div class="ad-panel-shell">
        <div class="ad-search" style="max-width:440px;margin-bottom:18px;">
            <i class="fas fa-search"></i>
            <input type="text" wire:model.live.debounce.300ms="searchTerm" placeholder="Введите название услуги">
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
                    @forelse ($services as $service)
                        <tr>
                            <td>{{ $service->id }}</td>
                            <td style="font-weight:600;">{{ $service->title_ru }}</td>
                            <td><span class="ad-counter">{{ $service->slug }}</span></td>
                            <td>
                                @if ((int)$service->status === 0)
                                    <span class="ad-badge ad-badge--on">Включен</span>
                                @else
                                    <span class="ad-badge ad-badge--off">Выключен</span>
                                @endif
                            </td>
                            <td>
                                <div class="ad-row-actions" style="justify-content:flex-end;">
                                    <a wire:navigate href="{{ route('editservice', ['service_slug' => $service->slug]) }}" class="ad-icon-btn" title="Редактировать"><i class="fas fa-pen"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="ad-empty"><i class="fas fa-screwdriver-wrench"></i>Услуг пока нет.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;">{{ $services->links('pagination-links') }}</div>
    </div>
</div>
