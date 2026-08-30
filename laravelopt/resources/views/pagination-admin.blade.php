@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination">
        <ul class="ad-pagination">
            {{-- Назад --}}
            @if ($paginator->onFirstPage())
                <li class="is-disabled"><span class="page-link"><i class="fas fa-chevron-left"></i></span></li>
            @else
                <li><button type="button" class="page-link" wire:click="previousPage" wire:loading.attr="disabled" rel="prev"><i class="fas fa-chevron-left"></i></button></li>
            @endif

            {{-- Номера --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="page-dots">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="is-active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li><button type="button" class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Вперёд --}}
            @if ($paginator->hasMorePages())
                <li><button type="button" class="page-link" wire:click="nextPage" wire:loading.attr="disabled" rel="next"><i class="fas fa-chevron-right"></i></button></li>
            @else
                <li class="is-disabled"><span class="page-link"><i class="fas fa-chevron-right"></i></span></li>
            @endif
        </ul>
    </nav>
@endif
