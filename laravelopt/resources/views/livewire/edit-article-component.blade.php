@section('title', 'Редактирование статьи — OPTECH')
@section('page-title', 'Редактирование статьи')
@section('breadcrumbs')<a wire:navigate href="{{ route('articles') }}">Статьи</a><span class="ad-breadcrumbs__sep"><i class="fas fa-chevron-right"></i></span>@endsection
<div>@include('livewire.partials.article-form', ['action' => 'updateArticle'])</div>
