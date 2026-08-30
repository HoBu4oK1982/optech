@section('title', 'Решение — OPTECH')
@section('page-title', 'Редактирование решения')
@section('breadcrumbs')<a wire:navigate href="{{ route('solutions') }}">Решения</a><span class="ad-breadcrumbs__sep"><i class="fas fa-chevron-right"></i></span>@endsection
<div>@include('livewire.partials.solution-form', ['action' => 'updateSolution'])</div>
