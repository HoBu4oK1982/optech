@section('title', 'Новая услуга — OPTECH')
@section('page-title', 'Новая услуга')
<div>
    <div class="ad-page-head"><div><h2>Создание услуги</h2></div><a wire:navigate href="{{ route('services') }}" class="ad-btn"><i class="fas fa-arrow-left"></i> Все услуги</a></div>
    <div class="ad-panel-shell">
        <div class="ad-form-grid">
            <label class="ad-field"><span>Название (RU)</span><input type="text" wire:model="title_ru" wire:blur="generateSlug"></label>
            <div class="ad-field" wire:ignore><span>Описание (RU)</span><textarea data-richtext wire:model.blur="description_ru">{!! $description_ru ?? '' !!}</textarea></div>
            <label class="ad-field"><span>URL (slug)</span><input type="text" wire:model="slug"></label>
            <div class="ad-field"><span>Изображение</span>
                <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;">
                    <label class="ad-file" x-data="{n:''}"><i class="fas fa-upload"></i><span x-text="n || 'Выбрать изображение'"></span><input type="file" wire:model="image" x-on:change="n = ($event.target.files[0] && $event.target.files[0].name) || ''"></label>
                    @if($image)<img src="{{ $image->temporaryUrl() }}" class="ad-thumb" style="width:120px;height:auto;object-fit:contain;background:#fff;">@endif
                </div>
            </div>
            <label class="ad-field"><span>Статус</span><select wire:model="status"><option value="0">Включен</option><option value="1">Выключен</option></select></label>
            <label class="ad-field"><span>Meta заголовок</span><input type="text" wire:model="meta_title"></label>
            <label class="ad-field"><span>Meta ключевые слова</span><textarea rows="3" wire:model="meta_keywords"></textarea></label>
            <label class="ad-field"><span>Meta описание</span><textarea rows="3" wire:model="meta_description"></textarea></label>
        </div>
        <div style="margin-top:20px;"><button class="ad-btn ad-btn-primary" wire:click="addService"><i class="fas fa-save"></i> Создать услугу</button></div>
    </div>
</div>
