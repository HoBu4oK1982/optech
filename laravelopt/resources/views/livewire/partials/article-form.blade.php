{{-- Общая форма статьи. Параметр: $action ('addArticle' | 'updateArticle'). Доступно: $categories, $products --}}
<form wire:submit="{{ $action }}">
    <div class="ad-form-actions-top">
        <a wire:navigate href="{{ route('articles') }}" class="ad-btn"><i class="fas fa-arrow-left"></i> К списку</a>
        <button type="submit" class="ad-btn ad-btn-primary">
            <span wire:loading.remove wire:target="{{ $action }}"><i class="fas fa-save"></i> Сохранить статью</span>
            <span wire:loading wire:target="{{ $action }}"><i class="fas fa-spinner fa-spin"></i> Сохранение...</span>
        </button>
    </div>

    <div class="ad-form-layout">
        <div class="ad-form-main">
            <section class="ad-form-section">
                <div class="ad-section-head"><span class="ad-section-icon"><i class="fas fa-newspaper"></i></span><div><h3>Основное</h3><p>Заголовок по языкам, URL и категория статьи.</p></div></div>
                <div data-langtabs>
                    <div class="ad-langtabs">
                        <button type="button" class="ad-langtab is-active" data-lang="ru">RU</button>
                        <button type="button" class="ad-langtab" data-lang="en">EN</button>
                        <button type="button" class="ad-langtab" data-lang="kz">KZ</button>
                    </div>
                    <div class="ad-langpane is-active" data-lang="ru">
                        <label class="ad-field"><span>Заголовок (RU) *</span><input type="text" wire:model="title_ru" wire:blur="generateSlug">@error('title_ru')<span class="ad-field-error">{{ $message }}</span>@enderror</label>
                        <div class="ad-field" wire:ignore><span>Анонс / лид (RU)</span><textarea data-richtext wire:model.blur="excerpt">{!! $excerpt ?? '' !!}</textarea></div>
                        <div class="ad-field" wire:ignore><span>Текст статьи (RU)</span><textarea data-richtext wire:model.blur="description_ru">{!! $description_ru ?? '' !!}</textarea></div>
                    </div>
                    <div class="ad-langpane" data-lang="en">
                        <label class="ad-field"><span>Заголовок (EN)</span><input type="text" wire:model="title_en"></label>
                        <div class="ad-field" wire:ignore><span>Анонс (EN)</span><textarea data-richtext wire:model.blur="excerpt_en">{!! $excerpt_en ?? '' !!}</textarea></div>
                        <div class="ad-field" wire:ignore><span>Текст (EN)</span><textarea data-richtext wire:model.blur="description_en">{!! $description_en ?? '' !!}</textarea></div>
                    </div>
                    <div class="ad-langpane" data-lang="kz">
                        <label class="ad-field"><span>Заголовок (KZ)</span><input type="text" wire:model="title_kz"></label>
                        <div class="ad-field" wire:ignore><span>Анонс (KZ)</span><textarea data-richtext wire:model.blur="excerpt_kz">{!! $excerpt_kz ?? '' !!}</textarea></div>
                        <div class="ad-field" wire:ignore><span>Текст (KZ)</span><textarea data-richtext wire:model.blur="description_kz">{!! $description_kz ?? '' !!}</textarea></div>
                    </div>
                </div>
                <div class="ad-fields-grid two" style="margin-top:14px;">
                    <label class="ad-field"><span>URL (slug) *</span><input type="text" wire:model="slug">@error('slug')<span class="ad-field-error">{{ $message }}</span>@enderror</label>
                    <label class="ad-field"><span>Категория статьи</span><select wire:model="category_id"><option value="">— нет —</option>@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></label>
                </div>
            </section>

            <section class="ad-form-section">
                <div class="ad-section-head"><span class="ad-section-icon"><i class="fas fa-user-edit"></i></span><div><h3>Параметры публикации</h3><p>Автор, источник, дата, теги — для schema.org Article.</p></div></div>
                <div class="ad-fields-grid two">
                    <label class="ad-field"><span>Автор</span><input type="text" wire:model="author"></label>
                    <label class="ad-field"><span>Источник</span><input type="text" wire:model="source"></label>
                    <label class="ad-field"><span>Дата публикации</span><input type="datetime-local" wire:model="published_at"></label>
                    <label class="ad-field"><span>Время чтения (мин)</span><input type="number" min="0" wire:model="reading_time"></label>
                    <label class="ad-field span-2"><span>Теги (через запятую)</span><input type="text" wire:model="tags" placeholder="оптика, монтаж, обзор"></label>
                </div>
            </section>

            <section class="ad-form-section">
                <div class="ad-section-head"><span class="ad-section-icon"><i class="fas fa-image"></i></span><div><h3>Изображения</h3></div></div>
                <div class="ad-fields-grid two">
                    <div class="ad-field"><span>Обложка</span>
                        <div class="ad-upload">
                            <input type="file" class="ad-upload__input" id="art-img" wire:model="image" accept="image/*">
                            @if($image)<div class="ad-upload__preview"><img src="{{ $image->temporaryUrl() }}"></div>
                            @elseif(!empty($existing_image))<div class="ad-upload__preview"><img src="{{ file_exists(public_path('assets/images/articles/'.$existing_image)) ? asset('assets/images/articles/'.$existing_image) : asset('assets/images/articles/'.$existing_image) }}"></div>@endif
                            <label for="art-img" class="ad-upload__zone"><div class="ad-upload__icon"><i class="fas fa-cloud-upload-alt"></i></div><div class="ad-upload__text"><strong>Загрузить</strong><span>до 5 МБ</span></div></label>
                        </div>
                    </div>
                    <div class="ad-field"><span>OG-картинка</span>
                        <div class="ad-upload">
                            <input type="file" class="ad-upload__input" id="art-og" wire:model="og_image" accept="image/*">
                            @if($og_image)<div class="ad-upload__preview"><img src="{{ $og_image->temporaryUrl() }}"></div>
                            @elseif(!empty($existing_og_image))<div class="ad-upload__preview"><img src="{{ file_exists(public_path('assets/images/articles/'.$existing_og_image)) ? asset('assets/images/articles/'.$existing_og_image) : asset('assets/images/articles/'.$existing_og_image) }}"></div>@endif
                            <label for="art-og" class="ad-upload__zone"><div class="ad-upload__icon"><i class="fas fa-share-alt"></i></div><div class="ad-upload__text"><strong>OG</strong><span>необязательно</span></div></label>
                        </div>
                    </div>
                </div>
            </section>

            <section class="ad-form-section">
                <div class="ad-section-head"><span class="ad-section-icon"><i class="fas fa-link"></i></span><div><h3>Перелинковка</h3><p>Связанные товары и категории (Ctrl/Cmd — множественный выбор).</p></div></div>
                <div class="ad-fields-grid two">
                    <label class="ad-field"><span>Связанные товары</span><select multiple size="6" wire:model="related_products">@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></label>
                    <label class="ad-field"><span>Связанные категории</span><select multiple size="6" wire:model="related_categories">@foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></label>
                </div>
            </section>

            @include('admin.partials.faq', ['property' => 'faq'])
            @include('admin.partials.seo')
        </div>

        <div class="ad-form-side">
            <section class="ad-form-section">
                <div class="ad-section-head mb-0"><span class="ad-section-icon"><i class="fas fa-rocket"></i></span><div><h3>Публикация</h3></div></div>
                <div class="ad-fields-grid" style="margin-top:16px;">
                    <label class="ad-field"><span>Статус</span><select wire:model="status"><option value="0">Опубликована</option><option value="1">Черновик</option></select></label>
                    <label class="ad-field"><span>Сортировка</span><input type="number" wire:model="sort_order" min="0"></label>
                    <button type="submit" class="ad-btn ad-btn-primary" style="width:100%;"><i class="fas fa-save"></i> Сохранить</button>
                </div>
            </section>
        </div>
    </div>
</form>
