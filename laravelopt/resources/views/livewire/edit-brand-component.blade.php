@section('title', 'Редактирование бренда — OPTECH')
@section('page-title', 'Редактирование бренда')
<div>
    <div class="ad-page-head">
        <div><h2>Обновление бренда</h2></div>
        <a wire:navigate href="{{ route('brands') }}" class="ad-btn"><i class="fas fa-arrow-left"></i> Все бренды</a>
    </div>

    <div class="ad-panel-shell">
        <div class="ad-form-grid">
            <label class="ad-field"><span>Название бренда</span><input type="text" wire:model="name"></label>
            <label class="ad-field"><span>Транслит</span><input type="text" wire:model="slug"></label>

            <div class="ad-field">
                <span>Изображение для обложки</span>
                <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
                    <label class="ad-file" x-data="{n:''}">
                        <i class="fas fa-upload"></i>
                        <span x-text="n || 'Выбрать изображение'"></span>
                        <input type="file" wire:model="newimage" x-on:change="n = ($event.target.files[0] && $event.target.files[0].name) || ''">
                    </label>
                    @if($image)
                        @php($bimg = file_exists(public_path('assets/images/brands/'.$image)) ? asset('assets/images/brands/'.$image) : asset('assets/images/brands/'.$image))
                        <img src="{{ $bimg }}" class="ad-thumb" style="width:120px;height:auto;object-fit:contain;background:#fff;" alt="" onerror="this.style.display='none'">
                    @endif
                    <span class="ad-counter">250×100 px, формат *.png</span>
                </div>
            </div>

            <label class="ad-field">
                <span>Статус</span>
                <select wire:model="status">
                    <option value="0">Включен</option>
                    <option value="1">Выключен</option>
                </select>
            </label>

            <div class="ad-field">
                <span>OG-картинка (для соцсетей)</span>
                <div class="ad-upload">
                    <input type="file" class="ad-upload__input" id="brand-edit-og" wire:model="og_image" accept="image/*">
                    @if($og_image)
                        <div class="ad-upload__preview"><img src="{{ $og_image->temporaryUrl() }}"></div>
                    @elseif(!empty($existing_og_image))
                        <div class="ad-upload__preview"><img src="{{ asset('assets/images/brands/' . $existing_og_image) }}" onerror="this.style.display='none'"></div>
                    @endif
                    <label for="brand-edit-og" class="ad-upload__zone">
                        <div class="ad-upload__icon"><i class="fas fa-share-alt"></i></div>
                        <div class="ad-upload__text"><strong>OG</strong><span>1200×630, необязательно</span></div>
                    </label>
                </div>
            </div>
        </div>

            <section class="ad-form-section">
                <div class="ad-section-head"><span class="ad-section-icon"><i class="fas fa-paragraph"></i></span><div><h3>SEO-тексты страницы бренда</h3><p>Выводятся над и под сеткой товаров бренда.</p></div></div>
                <div data-langtabs>
                    <div class="ad-langtabs"><button type="button" class="ad-langtab is-active" data-lang="ru">RU</button><button type="button" class="ad-langtab" data-lang="en">EN</button><button type="button" class="ad-langtab" data-lang="kz">KZ</button></div>
                    <div class="ad-langpane is-active" data-lang="ru"><div class="ad-field" wire:ignore><span>Текст над списком (RU)</span><textarea data-richtext wire:model.blur="seo_text_top">{!! $seo_text_top ?? '' !!}</textarea></div><div class="ad-field" wire:ignore><span>Текст под списком (RU)</span><textarea data-richtext wire:model.blur="seo_text_bottom">{!! $seo_text_bottom ?? '' !!}</textarea></div></div>
                    <div class="ad-langpane" data-lang="en"><div class="ad-field" wire:ignore><span>Над списком (EN)</span><textarea data-richtext wire:model.blur="seo_text_top_en">{!! $seo_text_top_en ?? '' !!}</textarea></div><div class="ad-field" wire:ignore><span>Под списком (EN)</span><textarea data-richtext wire:model.blur="seo_text_bottom_en">{!! $seo_text_bottom_en ?? '' !!}</textarea></div></div>
                    <div class="ad-langpane" data-lang="kz"><div class="ad-field" wire:ignore><span>Над списком (KZ)</span><textarea data-richtext wire:model.blur="seo_text_top_kz">{!! $seo_text_top_kz ?? '' !!}</textarea></div><div class="ad-field" wire:ignore><span>Под списком (KZ)</span><textarea data-richtext wire:model.blur="seo_text_bottom_kz">{!! $seo_text_bottom_kz ?? '' !!}</textarea></div></div>
                </div>
                <div class="ad-fields-grid" style="margin-top:14px;">
                    <label class="ad-field"><span>Заголовок в хлебных крошках</span><input type="text" wire:model="breadcrumb_title" placeholder="если пусто — используется название бренда"></label>
                </div>
            </section>

        @include('admin.partials.seo')

        <div style="margin-top:20px;">
            <button class="ad-btn ad-btn-primary" wire:click="updateBrand"><i class="fas fa-save"></i> Обновить</button>
        </div>
    </div>
</div>
