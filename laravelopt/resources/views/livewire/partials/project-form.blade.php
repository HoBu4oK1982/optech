{{-- Форма проекта. Параметр: $action. Доступно: $solutions, $products --}}
<form wire:submit="{{ $action }}">
    <div class="ad-form-actions-top">
        <a wire:navigate href="{{ route('projects') }}" class="ad-btn"><i class="fas fa-arrow-left"></i> К списку</a>
        <button type="submit" class="ad-btn ad-btn-primary"><span wire:loading.remove wire:target="{{ $action }}"><i class="fas fa-save"></i> Сохранить</span><span wire:loading wire:target="{{ $action }}"><i class="fas fa-spinner fa-spin"></i> Сохранение...</span></button>
    </div>
    <div class="ad-form-layout">
        <div class="ad-form-main">
            <section class="ad-form-section">
                <div class="ad-section-head"><span class="ad-section-icon"><i class="fas fa-briefcase"></i></span><div><h3>Основное</h3></div></div>
                <div data-langtabs>
                    <div class="ad-langtabs"><button type="button" class="ad-langtab is-active" data-lang="ru">RU</button><button type="button" class="ad-langtab" data-lang="en">EN</button><button type="button" class="ad-langtab" data-lang="kz">KZ</button></div>
                    <div class="ad-langpane is-active" data-lang="ru"><label class="ad-field"><span>Название (RU) *</span><input type="text" wire:model="title_ru" wire:blur="generateSlug">@error('title_ru')<span class="ad-field-error">{{ $message }}</span>@enderror</label><div class="ad-field" wire:ignore><span>Описание (RU)</span><textarea data-richtext wire:model.blur="description_ru">{!! $description_ru !!}</textarea></div></div>
                    <div class="ad-langpane" data-lang="en"><label class="ad-field"><span>Название (EN)</span><input type="text" wire:model="title_en"></label><div class="ad-field" wire:ignore><span>Описание (EN)</span><textarea data-richtext wire:model.blur="description_en">{!! $description_en ?? '' !!}</textarea></div></div>
                    <div class="ad-langpane" data-lang="kz"><label class="ad-field"><span>Название (KZ)</span><input type="text" wire:model="title_kz"></label><div class="ad-field" wire:ignore><span>Описание (KZ)</span><textarea data-richtext wire:model.blur="description_kz">{!! $description_kz ?? '' !!}</textarea></div></div>
                </div>
                <label class="ad-field" style="margin-top:14px;"><span>URL (slug) *</span><input type="text" wire:model="slug">@error('slug')<span class="ad-field-error">{{ $message }}</span>@enderror</label>
            </section>

            <section class="ad-form-section">
                <div class="ad-section-head"><span class="ad-section-icon"><i class="fas fa-info-circle"></i></span><div><h3>Данные кейса</h3></div></div>
                <div class="ad-fields-grid three">
                    <label class="ad-field"><span>Клиент</span><input type="text" wire:model="client"></label>
                    <label class="ad-field"><span>Год</span><input type="text" wire:model="project_year" placeholder="2025"></label>
                    <label class="ad-field"><span>Локация</span><input type="text" wire:model="location" placeholder="Алматы"></label>
                </div>
            </section>

            <section class="ad-form-section">
                <div class="ad-section-head mb-0" style="display:flex;justify-content:space-between;width:100%;">
                    <div style="display:flex;gap:14px;"><span class="ad-section-icon"><i class="fas fa-chart-bar"></i></span><div><h3>Результаты</h3><p>Метрики кейса: подпись и значение.</p></div></div>
                    <button type="button" class="ad-btn ad-btn-sm" wire:click="addRepeaterItem('result_metrics', { label: '', value: '' })"><i class="fas fa-plus"></i> Добавить</button>
                </div>
                <div class="ad-repeater" style="margin-top:16px;">
                    @forelse($result_metrics as $i => $m)
                        <div class="ad-repeater__item" wire:key="rm-{{ $i }}">
                            <div class="ad-repeater__num">{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</div>
                            <div class="ad-repeater__fields" style="grid-template-columns:1fr 1fr;">
                                <label class="ad-field"><span>Подпись</span><input type="text" wire:model="result_metrics.{{ $i }}.label" placeholder="Рост заявок"></label>
                                <label class="ad-field"><span>Значение</span><input type="text" wire:model="result_metrics.{{ $i }}.value" placeholder="+45%"></label>
                            </div>
                            <button type="button" class="ad-repeater__remove" wire:click="removeRepeaterItem('result_metrics', {{ $i }})"><i class="fas fa-times"></i></button>
                        </div>
                    @empty
                        <div class="ad-empty" style="padding:24px;"><i class="fas fa-chart-bar"></i>Метрик пока нет.</div>
                    @endforelse
                </div>
            </section>

            <section class="ad-form-section">
                <div class="ad-section-head"><span class="ad-section-icon"><i class="fas fa-image"></i></span><div><h3>Изображения</h3></div></div>
                <div class="ad-fields-grid two">
                    <div class="ad-field"><span>Главное фото</span><div class="ad-upload"><input type="file" class="ad-upload__input" id="pr-img" wire:model="image" accept="image/*">@if($image)<div class="ad-upload__preview"><img src="{{ $image->temporaryUrl() }}"></div>@elseif(!empty($existing_image))<div class="ad-upload__preview"><img src="{{ asset('assets/images/projects/' . $existing_image) }}"></div>@endif<label for="pr-img" class="ad-upload__zone"><div class="ad-upload__icon"><i class="fas fa-cloud-upload-alt"></i></div><div class="ad-upload__text"><strong>Загрузить</strong><span>до 5 МБ</span></div></label></div></div>
                    <div class="ad-field"><span>OG-картинка</span><div class="ad-upload"><input type="file" class="ad-upload__input" id="pr-og" wire:model="og_image" accept="image/*">@if($og_image)<div class="ad-upload__preview"><img src="{{ $og_image->temporaryUrl() }}"></div>@elseif(!empty($existing_og_image))<div class="ad-upload__preview"><img src="{{ asset('assets/images/projects/' . $existing_og_image) }}"></div>@endif<label for="pr-og" class="ad-upload__zone"><div class="ad-upload__icon"><i class="fas fa-share-alt"></i></div><div class="ad-upload__text"><strong>OG</strong><span>необязательно</span></div></label></div></div>
                </div>
                <div class="ad-field" style="margin-top:16px;"><span>Галерея</span>
                    <div class="ad-gallery">
                        @foreach($gallery as $i => $g)<div class="ad-gallery__item" wire:key="g-{{ $i }}"><img src="{{ asset('assets/images/projects/' . $g) }}"><button type="button" class="ad-gallery__remove" wire:click="removeGallery({{ $i }})"><i class="fas fa-times"></i></button></div>@endforeach
                        @foreach($galleryUploads as $i => $gu)<div class="ad-gallery__item" wire:key="gu-{{ $i }}"><img src="{{ $gu->temporaryUrl() }}"></div>@endforeach
                    </div>
                    <label for="pr-gal" class="ad-upload__zone" style="margin-top:10px;"><div class="ad-upload__icon"><i class="fas fa-images"></i></div><div class="ad-upload__text"><strong>Добавить в галерею</strong><span>несколько файлов</span></div></label>
                    <input type="file" class="ad-upload__input" id="pr-gal" wire:model="galleryUploads" accept="image/*" multiple>
                </div>
            </section>

            <section class="ad-form-section">
                <div class="ad-section-head"><span class="ad-section-icon"><i class="fas fa-link"></i></span><div><h3>Перелинковка</h3></div></div>
                <div class="ad-fields-grid two">
                    <label class="ad-field"><span>Связанные решения</span><select multiple size="6" wire:model="related_solutions">@foreach($solutions as $s)<option value="{{ $s->id }}">{{ $s->title_ru }}</option>@endforeach</select></label>
                    <label class="ad-field"><span>Связанные товары</span><select multiple size="6" wire:model="related_products">@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></label>
                </div>
            </section>

            @include('admin.partials.seo')
        </div>
        <div class="ad-form-side">
            <section class="ad-form-section">
                <div class="ad-section-head mb-0"><span class="ad-section-icon"><i class="fas fa-rocket"></i></span><div><h3>Публикация</h3></div></div>
                <div class="ad-fields-grid" style="margin-top:16px;">
                    <label class="ad-field"><span>Статус</span><select wire:model="status"><option value="0">Опубликован</option><option value="1">Скрыт</option></select></label>
                    <label class="ad-field"><span>Сортировка</span><input type="number" wire:model="sort_order" min="0"></label>
                    <button type="submit" class="ad-btn ad-btn-primary" style="width:100%;"><i class="fas fa-save"></i> Сохранить</button>
                </div>
            </section>
        </div>
    </div>
</form>
