@section('title', 'Редактирование товара — OPTECH')
@section('page-title', 'Редактирование товара')
@section('breadcrumbs')
    <a wire:navigate href="{{ route('products') }}">Товары</a>
    <span class="ad-breadcrumbs__sep"><i class="fas fa-chevron-right"></i></span>
@endsection

<div>
    @include('livewire.partials.product-form', ['action' => 'updateProduct'])
</div>
