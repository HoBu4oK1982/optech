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
            <div class="ad-field">
                <span>OG-картинка (для соцсетей)</span>
                <div class="ad-upload">
                    <input type="file" class="ad-upload__input" id="service-edit-og" wire:model="og_image" accept="image/*">
                    @if($og_image)
                        <div class="ad-upload__preview"><img src="{{ $og_image->temporaryUrl() }}"></div>
                    @elseif(!empty($existing_og_image))
                        <div class="ad-upload__preview"><img src="{{ asset('assets/images/services/' . $existing_og_image) }}" onerror="this.style.display='none'"></div>
                    @endif
                    <label for="service-edit-og" class="ad-upload__zone">
                        <div class="ad-upload__icon"><i class="fas fa-share-alt"></i></div>
                        <div class="ad-upload__text"><strong>OG</strong><span>1200×630, необязательно</span></div>
                    </label>
                </div>
            </div>
        </div>
        @include('admin.partials.seo')

        <div style="margin-top:20px;"><button class="ad-btn ad-btn-primary" wire:click="updateService"><i class="fas fa-save"></i> Обновить</button></div>
    </div>
</div>
