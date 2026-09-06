@section('title', 'Новое спецпредложение — OPTECH')
@section('page-title', 'Новое спецпредложение')
<div>
    <div class="ad-page-head"><div><h2>Создание спецпредложения</h2></div><a wire:navigate href="{{ route('offers') }}" class="ad-btn"><i class="fas fa-arrow-left"></i> Все спецпредложения</a></div>
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
            <div class="ad-field">
                <span>OG-картинка (для соцсетей)</span>
                <div class="ad-upload">
                    <input type="file" class="ad-upload__input" id="offer-add-og" wire:model="og_image" accept="image/*">
                    @if($og_image)
                        <div class="ad-upload__preview"><img src="{{ $og_image->temporaryUrl() }}"></div>
                    @elseif(!empty($existing_og_image))
                        <div class="ad-upload__preview"><img src="{{ asset('assets/images/offers/' . $existing_og_image) }}" onerror="this.style.display='none'"></div>
                    @endif
                    <label for="offer-add-og" class="ad-upload__zone">
                        <div class="ad-upload__icon"><i class="fas fa-share-alt"></i></div>
                        <div class="ad-upload__text"><strong>OG</strong><span>1200×630, необязательно</span></div>
                    </label>
                </div>
            </div>
        </div>
        @include('admin.partials.seo')

        <div style="margin-top:20px;"><button class="ad-btn ad-btn-primary" wire:click="addOffer"><i class="fas fa-save"></i> Создать спецпредложение</button></div>
    </div>
</div>
