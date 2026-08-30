@section('title', 'Новый товар — OPTECH')
@section('page-title', 'Новый товар')
@section('breadcrumbs')
    <a wire:navigate href="{{ route('products') }}">Товары</a>
    <span class="ad-breadcrumbs__sep"><i class="fas fa-chevron-right"></i></span>
@endsection

<div>
    @include('livewire.partials.product-form', ['action' => 'addProduct'])
</div>
