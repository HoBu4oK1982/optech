@section('title', 'Товары — OPTECH')
@section('page-title', 'Товары')

<div>
    <div class="ad-page-head">
        <div>
            <h2>Товары</h2>
        </div>
        <a wire:navigate href="{{ route('addproduct') }}" class="ad-btn ad-btn-primary"><i class="fas fa-plus"></i> Новый товар</a>
    </div>

    <div class="ad-panel-shell">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:18px;flex-wrap:wrap;">
            <div class="ad-search">
                <i class="fas fa-search"></i>
                <input type="text" wire:model.live.debounce.400ms="searchTerm" placeholder="Поиск по названию...">
            </div>
            <div class="ad-counter">Всего: {{ $products->total() }}</div>
        </div>

        <div class="ad-table-wrap">
            <table class="ad-table">
                <thead>
                    <tr>
                        <th style="width:64px;">Фото</th>
                        <th>Название</th>
                        <th>Категория</th>
                        <th>Цена</th>
                        <th>Наличие</th>
                        <th>Статус</th>
                        <th style="text-align:right;">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr wire:key="prod-{{ $product->id }}">
                            <td>
                                @php($pimg = $product->image
                                    ? (file_exists(public_path('assets/images/products/'.$product->image))
                                        ? asset('assets/images/products/'.$product->image)
                                        : asset('assets/images/products/'.$product->image))
                                    : null)
                                @if($pimg)
                                    <img class="ad-thumb" src="{{ $pimg }}" alt="" onerror="this.style.display='none';this.insertAdjacentHTML('afterend','<div class=\'ad-thumb\' style=\'display:grid;place-items:center;\'><i class=\'fas fa-box\' style=\'opacity:.4;\'></i></div>')">
                                @else
                                    <div class="ad-thumb" style="display:grid;place-items:center;"><i class="fas fa-box" style="opacity:.4;"></i></div>
                                @endif
                            </td>
                            <td>
                                <div style="font-weight:700;">{{ $product->name }}</div>
                                <div class="ad-counter">{{ $product->SKU ?: '—' }} · /{{ $product->slug }}</div>
                            </td>
                            <td>{{ $product->category->name ?? '—' }}</td>
                            <td>
                                @if($product->price_on_request)
                                    <span class="ad-badge ad-badge--warn">По запросу</span>
                                @elseif($product->price)
                                    <strong>{{ number_format($product->price, 0, '.', ' ') }} {{ $product->currency }}</strong>
                                @else
                                    <span class="ad-counter">не указана</span>
                                @endif
                            </td>
                            <td>
                                @php($av = ['InStock' => 'В наличии', 'OutOfStock' => 'Нет', 'PreOrder' => 'Предзаказ', 'BackOrder' => 'Под заказ'])
                                <span class="ad-badge {{ $product->availability === 'InStock' ? 'ad-badge--on' : 'ad-badge--off' }}">{{ $av[$product->availability] ?? '—' }}</span>
                            </td>
                            <td>
                                <button wire:click="toggleStatus({{ $product->id }})" class="ad-badge {{ (int)$product->status === 0 ? 'ad-badge--on' : 'ad-badge--off' }}" style="border:none;cursor:pointer;">
                                    {{ (int)$product->status === 0 ? 'Опубликован' : 'Скрыт' }}
                                </button>
                            </td>
                            <td>
                                <div class="ad-row-actions">
                                    <a wire:navigate href="{{ route('editproduct', $product->slug) }}" class="ad-icon-btn" title="Редактировать"><i class="fas fa-pen"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="ad-empty"><i class="fas fa-box-open"></i>Товары не найдены. Создайте первый товар.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:18px;">{{ $products->links('pagination-admin') }}</div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        if (window.__optechPagerScroll) return;
        window.__optechPagerScroll = true;
        // При клике по пагинации плавно возвращаем страницу наверх
        document.addEventListener('click', function (e) {
            if (e.target.closest('.ad-pagination')) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    })();
</script>
@endpush
