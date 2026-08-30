<?php

namespace App\Livewire\Concerns;

/**
 * Хелперы для повторяющихся блоков (FAQ, метрики результата и т.п.),
 * которые хранятся в JSON-полях и редактируются как массивы в Livewire.
 *
 * Использование в компоненте:
 *   use WithRepeaters;
 *   public array $faq = [];
 *   // в blade:  wire:click="addRepeaterItem('faq', {question:'', answer:''})"
 */
trait WithRepeaters
{
    /**
     * Добавить пустой элемент в массив-репитер.
     */
    public function addRepeaterItem(string $property, array $template = []): void
    {
        $current = $this->{$property} ?? [];
        $current[] = $template;
        $this->{$property} = array_values($current);
    }

    /**
     * Удалить элемент репитера по индексу.
     */
    public function removeRepeaterItem(string $property, int $index): void
    {
        $current = $this->{$property} ?? [];
        unset($current[$index]);
        $this->{$property} = array_values($current);
    }

    /**
     * Удалить картинку из массива-галереи (по индексу).
     */
    public function removeGalleryItem(string $property, int $index): void
    {
        $this->removeRepeaterItem($property, $index);
    }
}
