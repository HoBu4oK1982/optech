@section('title', 'Редактирование услуги — OPTECH')
@section('page-title', 'Редактирование услуги')
<div>
    <div class="ad-page-head"><div><h2>Обновление услуги</h2></div><a wire:navigate href="{{ route('services') }}" class="ad-btn"><i class="fas fa-arrow-left"></i> Все услуги</a></div>
    <div class="ad-panel-shell">
        <div data-langtabs>
            <div class="ad-langtabs"><button type="button" class="ad-langtab is-active" data-lang="ru">RU</button><button type="button" class="ad-langtab" data-lang="en">EN</button><button type="button" class="ad-langtab" data-lang="kz">KZ</button></div>
            <div class="ad-langpane is-active" data-lang="ru">
                <label class="ad-field"><span>Название (RU)</span><input type="text" wire:model="title_ru" wire:blur="generateSlug"></label>
                <div class="ad-field" wire:ignore><span>Описание (RU)</span><textarea data-richtext wire:model.blur="description_ru">{!! $description_ru ?? '' !!}</textarea></div>
            </div>
            <div class="ad-langpane" data-lang="en">
                <label class="ad-field"><span>Название (EN)</span><input type="text" wire:model="title_en"></label>
                <div class="ad-field" wire:ignore><span>Описание (EN)</span><textarea data-richtext wire:model.blur="description_en">{!! $description_en ?? '' !!}</textarea></div>
            </div>
            <div class="ad-langpane" data-lang="kz">
                <label class="ad-field"><span>Название (KZ)</span><input type="text" wire:model="title_kz"></label>
                <div class="ad-field" wire:ignore><span>Описание (KZ)</span><textarea data-richtext wire:model.blur="description_kz">{!! $description_kz ?? '' !!}</textarea></div>
            </div>
        </div>
        <div class="ad-form-grid" style="margin-top:14px;">
            <label class="ad-field"><span>URL (slug)</span><input type="text" wire:model="slug"></label>
            <div class="ad-field"><span>Изображение</span>
                <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;">
                    <label class="ad-file" x-data="{n:''}"><i class="fas fa-upload"></i><span x-text="n || 'Выбрать изображение'"></span><input type="file" wire:model="newimage" x-on:change="n = ($event.target.files[0] && $event.target.files[0].name) || ''"></label>
                    @if($image)@php($simg = file_exists(public_path('assets/images/services/'.$image)) ? asset('assets/images/services/'.$image) : asset('assets/images/services/'.$image))<img src="{{ $simg }}" class="ad-thumb" style="width:120px;height:auto;object-fit:contain;background:#fff;" onerror="this.style.display='none'">@endif
                </div>
            </div>
            <label class="ad-field"><span>Статус</span><select wire:model="status"><option value="0">Включен</option><option value="1">Выключен</option></select></label>
            <label class="ad-field"><span>Meta заголовок</span><input type="text" wire:model="meta_title"></label>
            <label class="ad-field"><span>Meta ключевые слова</span><textarea rows="3" wire:model="meta_keywords"></textarea></label>
            <label class="ad-field"><span>Meta описание</span><textarea rows="3" wire:model="meta_description"></textarea></label>
        </div>
        <div style="margin-top:20px;"><button class="ad-btn ad-btn-primary" wire:click="updateService"><i class="fas fa-save"></i> Обновить</button></div>
    </div>
</div>
