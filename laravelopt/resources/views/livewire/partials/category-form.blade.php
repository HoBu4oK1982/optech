{{--
    Общая форма категории. Параметр: $action ('addCategory' | 'updateCategory')
    Доступно: $categories и public-свойства компонента.
--}}
<form wire:submit="{{ $action }}">
    <div class="ad-form-actions-top">
        <a wire:navigate href="{{ route('categories') }}" class="ad-btn"><i class="fas fa-arrow-left"></i> К списку</a>
        <button type="submit" class="ad-btn ad-btn-primary">
            <span wire:loading.remove wire:target="{{ $action }}"><i class="fas fa-save"></i> Сохранить категорию</span>
            <span wire:loading wire:target="{{ $action }}"><i class="fas fa-spinner fa-spin"></i> Сохранение...</span>
        </button>
    </div>

    <div class="ad-form-layout">
        <div class="ad-form-main">
            {{-- Основное --}}
            <section class="ad-form-section">
                <div class="ad-section-head">
                    <span class="ad-section-icon"><i class="fas fa-sitemap"></i></span>
                    <div><h3>Основные данные</h3><p>Название по языкам, URL и место в дереве категорий.</p></div>
                </div>

                <div data-langtabs>
                    <div class="ad-langtabs">
                        <button type="button" class="ad-langtab is-active" data-lang="ru">RU</button>
                        <button type="button" class="ad-langtab" data-lang="en">EN</button>
                        <button type="button" class="ad-langtab" data-lang="kz">KZ</button>
                    </div>
                    <div class="ad-langpane is-active" data-lang="ru">
                        <label class="ad-field">
                            <span>Название (RU) *</span>
                            <input type="text" wire:model="name" wire:blur="generateSlug" placeholder="Сварочные аппараты">
                            @error('name')<span class="ad-field-error">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <div class="ad-langpane" data-lang="en">
                        <label class="ad-field"><span>Название (EN)</span><input type="text" wire:model="name_en"></label>
                    </div>
                    <div class="ad-langpane" data-lang="kz">
                        <label class="ad-field"><span>Название (KZ)</span><input type="text" wire:model="name_kz"></label>
                    </div>
                </div>

                <div class="ad-fields-grid two" style="margin-top:14px;">
                    <label class="ad-field">
                        <span>URL (slug) *</span>
                        <input type="text" wire:model="slug" placeholder="svarochnye-apparaty">
                        @error('slug')<span class="ad-field-error">{{ $message }}</span>@enderror
                    </label>
                    <label class="ad-field">
                        <span>Родительская категория</span>
                        <select wire:model="parent_id">
                            <option value="">— корневая —</option>
                            @include('livewire.partials._category-options', ['items' => $categories, 'depth' => 0, 'category_id' => $parent_id])
                        </select>
                    </label>
                    <label class="ad-field"><span>Иконка (CSS-класс)</span><input type="text" wire:model="icon" placeholder="fas fa-bolt"></label>
                    <label class="ad-field"><span>Заголовок в хлебных крошках</span><input type="text" wire:model="breadcrumb_title" placeholder="если отличается от названия"></label>
                </div>
            </section>

            {{-- SEO-тексты листинга --}}
            <section class="ad-form-section">
                <div class="ad-section-head">
                    <span class="ad-section-icon"><i class="fas fa-paragraph"></i></span>
                    <div><h3>SEO-тексты страницы</h3></div>
                </div>
                <div data-langtabs>
                    <div class="ad-langtabs">
                        <button type="button" class="ad-langtab is-active" data-lang="ru">RU</button>
                        <button type="button" class="ad-langtab" data-lang="en">EN</button>
                        <button type="button" class="ad-langtab" data-lang="kz">KZ</button>
                    </div>
                    <div class="ad-langpane is-active" data-lang="ru">
                        <div class="ad-field" wire:ignore><span>Текст над списком (RU)</span><textarea data-richtext wire:model.blur="seo_text_top">{!! $seo_text_top ?? '' !!}</textarea></div>
                        <div class="ad-field" wire:ignore><span>Текст под списком (RU)</span><textarea data-richtext wire:model.blur="seo_text_bottom">{!! $seo_text_bottom ?? '' !!}</textarea></div>
                    </div>
                    <div class="ad-langpane" data-lang="en">
                        <div class="ad-field" wire:ignore><span>Текст над списком (EN)</span><textarea data-richtext wire:model.blur="seo_text_top_en">{!! $seo_text_top_en ?? '' !!}</textarea></div>
                        <div class="ad-field" wire:ignore><span>Текст под списком (EN)</span><textarea data-richtext wire:model.blur="seo_text_bottom_en">{!! $seo_text_bottom_en ?? '' !!}</textarea></div>
                    </div>
                    <div class="ad-langpane" data-lang="kz">
                        <div class="ad-field" wire:ignore><span>Текст над списком (KZ)</span><textarea data-richtext wire:model.blur="seo_text_top_kz">{!! $seo_text_top_kz ?? '' !!}</textarea></div>
                        <div class="ad-field" wire:ignore><span>Текст под списком (KZ)</span><textarea data-richtext wire:model.blur="seo_text_bottom_kz">{!! $seo_text_bottom_kz ?? '' !!}</textarea></div>
                    </div>
                </div>
            </section>

            {{-- Изображения --}}
            <section class="ad-form-section">
                <div class="ad-section-head">
                    <span class="ad-section-icon"><i class="fas fa-image"></i></span>
                    <div><h3>Изображения</h3><p>Картинка категории и OG-картинка для соцсетей.</p></div>
                </div>
                <div class="ad-fields-grid two">
                    <div class="ad-field">
                        <span>Изображение категории</span>
                        <div class="ad-upload">
                            <input type="file" class="ad-upload__input" id="cat-img" wire:model="image" accept="image/*">
                            @if($image)
                                <div class="ad-upload__preview"><img src="{{ $image->temporaryUrl() }}" alt=""></div>
                            @elseif(!empty($existing_image))
                                <div class="ad-upload__preview"><img src="{{ asset('assets/images/categories/' . $existing_image) }}" alt=""></div>
                            @endif
                            <label for="cat-img" class="ad-upload__zone">
                                <div class="ad-upload__icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                <div class="ad-upload__text"><strong>Загрузить</strong><span>JPG, PNG, WebP до 5 МБ</span></div>
                            </label>
                        </div>
                    </div>
                    <div class="ad-field">
                        <span>OG-картинка</span>
                        <div class="ad-upload">
                            <input type="file" class="ad-upload__input" id="cat-og" wire:model="og_image" accept="image/*">
                            @if($og_image)
                                <div class="ad-upload__preview"><img src="{{ $og_image->temporaryUrl() }}" alt=""></div>
                            @elseif(!empty($existing_og_image))
                                <div class="ad-upload__preview"><img src="{{ asset('assets/images/categories/' . $existing_og_image) }}" alt=""></div>
                            @endif
                            <label for="cat-og" class="ad-upload__zone">
                                <div class="ad-upload__icon"><i class="fas fa-share-alt"></i></div>
                                <div class="ad-upload__text"><strong>Загрузить OG</strong><span>Необязательно</span></div>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            @include('admin.partials.faq', ['property' => 'faq'])
            @include('admin.partials.seo')
        </div>

        <div class="ad-form-side">
            <section class="ad-form-section">
                <div class="ad-section-head mb-0">
                    <span class="ad-section-icon"><i class="fas fa-rocket"></i></span>
                    <div><h3>Публикация</h3></div>
                </div>
                <div class="ad-fields-grid" style="margin-top:16px;">
                    <label class="ad-field">
                        <span>Статус</span>
                        <select wire:model="status">
                            <option value="0">Опубликована</option>
                            <option value="1">Скрыта</option>
                        </select>
                    </label>
                    <label class="ad-field"><span>Порядок сортировки</span><input type="number" wire:model="sort_order" min="0"></label>
                    <button type="submit" class="ad-btn ad-btn-primary" style="width:100%;"><i class="fas fa-save"></i> Сохранить</button>
                </div>
            </section>
        </div>
    </div>
</form>
