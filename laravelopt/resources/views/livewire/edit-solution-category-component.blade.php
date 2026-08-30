@section('title', 'Категория решений — OPTECH')
@section('page-title', 'Редактирование категории решений')
@section('breadcrumbs')<a wire:navigate href="{{ route('solcategories') }}">Категории решений</a><span class="ad-breadcrumbs__sep"><i class="fas fa-chevron-right"></i></span>@endsection
<div>@include('livewire.partials.solution-category-form', ['action' => 'updateSolutionCategory'])</div>
