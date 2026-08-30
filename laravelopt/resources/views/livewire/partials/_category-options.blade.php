{{-- Рекурсивный вывод опций категорий с отступами. Параметры: $items, $depth (int) --}}
@php($depth = $depth ?? 0)
@foreach($items as $cat)
    <option value="{{ $cat->id }}" @selected((string)($category_id ?? '') === (string)$cat->id)>
        {{ str_repeat('— ', $depth) }}{{ $cat->name }}
    </option>
    @if($cat->children && $cat->children->count())
        @include('livewire.partials._category-options', ['items' => $cat->children, 'depth' => $depth + 1])
    @endif
@endforeach
