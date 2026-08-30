{{--
    FAQ-репитер (-> FAQPage schema на фронте).
    Требует: публичное свойство-массив $faq и trait WithRepeaters.
    Параметры: $property (default 'faq')
--}}
@php($property = $property ?? 'faq')

<section class="ad-form-section">
    <div class="ad-section-head mb-0" style="justify-content:space-between;display:flex;width:100%;">
        <div style="display:flex;gap:14px;align-items:flex-start;">
            <span class="ad-section-icon"><i class="fas fa-question"></i></span>
            <div>
                <h3>FAQ</h3>
                <p>Вопросы и ответы. На фронте формируют FAQ-разметку и расширенные сниппеты.</p>
            </div>
        </div>
        <button type="button" class="ad-btn ad-btn-sm" wire:click="addRepeaterItem('{{ $property }}', { question: '', answer: '' })"><i class="fas fa-plus"></i> Добавить</button>
    </div>

    <div class="ad-repeater" style="margin-top:16px;">
        @forelse(($this->{$property} ?? []) as $i => $item)
            <div class="ad-repeater__item" wire:key="{{ $property }}-{{ $i }}">
                <div class="ad-repeater__num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</div>
                <div class="ad-repeater__fields">
                    <label class="ad-field"><span>Вопрос</span><input type="text" wire:model="{{ $property }}.{{ $i }}.question" placeholder="Какой срок доставки?"></label>
                    <label class="ad-field"><span>Ответ</span><textarea rows="2" wire:model="{{ $property }}.{{ $i }}.answer" placeholder="Ответ для пользователя"></textarea></label>
                </div>
                <button type="button" class="ad-repeater__remove" wire:click="removeRepeaterItem('{{ $property }}', {{ $i }})" title="Удалить"><i class="fas fa-times"></i></button>
            </div>
        @empty
            <div class="ad-empty" style="padding:24px;"><i class="fas fa-comments"></i>Пока нет вопросов. Нажмите «Добавить».</div>
        @endforelse
    </div>
</section>
