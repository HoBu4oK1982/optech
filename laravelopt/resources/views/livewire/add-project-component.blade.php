@section('title', 'Новый проект — OPTECH')
@section('page-title', 'Новый проект')
@section('breadcrumbs')<a wire:navigate href="{{ route('projects') }}">Проекты</a><span class="ad-breadcrumbs__sep"><i class="fas fa-chevron-right"></i></span>@endsection
<div>@include('livewire.partials.project-form', ['action' => 'addProject'])</div>
