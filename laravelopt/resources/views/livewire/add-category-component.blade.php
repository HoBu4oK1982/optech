@section('title', 'Новая категория — OPTECH')
@section('page-title', 'Новая категория')
@section('breadcrumbs')
    <a wire:navigate href="{{ route('categories') }}">Категории</a>
    <span class="ad-breadcrumbs__sep"><i class="fas fa-chevron-right"></i></span>
@endsection
<div>
    @include('livewire.partials.category-form', ['action' => 'addCategory'])
</div>
