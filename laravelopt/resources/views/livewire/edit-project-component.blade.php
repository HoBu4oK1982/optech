@section('title', 'Проект — OPTECH')
@section('page-title', 'Редактирование проекта')
@section('breadcrumbs')<a wire:navigate href="{{ route('projects') }}">Проекты</a><span class="ad-breadcrumbs__sep"><i class="fas fa-chevron-right"></i></span>@endsection
<div>@include('livewire.partials.project-form', ['action' => 'updateProject'])</div>
