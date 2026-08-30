@section('title', 'Редактирование категории — OPTECH')
@section('page-title', 'Редактирование категории')
@section('breadcrumbs')
    <a wire:navigate href="{{ route('categories') }}">Категории</a>
    <span class="ad-breadcrumbs__sep"><i class="fas fa-chevron-right"></i></span>
@endsection
<div>
    @include('livewire.partials.category-form', ['action' => 'updateCategory'])
</div>
