{{--
    Общая форма товара. Параметры:
      $action  — метод сохранения ('addProduct' | 'updateProduct')
    Доступно из компонента: $categories, $brands и все public-свойства.
--}}
<form wire:submit="{{ $action }}">
    <div class="ad-form-actions-top">
        <a wire:navigate href="{{ route('products') }}" class="ad-btn"><i class="fas fa-arrow-left"></i> К списку</a>
        <button type="submit" class="ad-btn ad-btn-primary">
            <span wire:loading.remove wire:target="{{ $action }}"><i class="fas fa-save"></i> Сохранить товар</span>
            <span wire:loading wire:target="{{ $action }}"><i class="fas fa-spinner fa-spin"></i> Сохранение...</span>
        </button>
    </div>

    <div class="ad-form-layout">
        <div class="ad-form-main">
            {{-- Основное --}}
            <section class="ad-form-section">
                <div class="ad-section-head">
                    <span class="ad-section-icon"><i class="fas fa-box"></i></span>
                    <div><h3>Основные данные</h3><p>Название, URL, артикул, категория и бренд.</p></div>
                </div>
                <div class="ad-fields-grid two">
                    <label class="ad-field span-2">
                        <span>Название товара *</span>
                        <input type="text" wire:model="name" wire:blur="generateSlug" placeholder="Оптический рефлектометр EXFO MaxTester">
                        @error('name')<span class="ad-field-error">{{ $message }}</span>@enderror
                    </label>
                    <label class="ad-field">
                        <span>URL (slug) *</span>
                        <input type="text" wire:model="slug" placeholder="exfo-maxtester">
                        @error('slug')<span class="ad-field-error">{{ $message }}</span>@enderror
                    </label>
                    <label class="ad-field"><span>Артикул (SKU)</span><input type="text" wire:model="SKU" placeholder="MAX-720C"></label>
                    <label class="ad-field">
                        <span>Категория</span>
                        <select wire:model="category_id">
                            <option value="">— не выбрана —</option>
                            @include('livewire.partials._category-options', ['items' => $categories, 'depth' => 0])
                        </select>
                    </label>
                    <label class="ad-field">
                        <span>Бренд</span>
                        <select wire:model="brand_id">
                            <option value="">— не выбран —</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </section>

            {{-- Контент по языкам --}}
            <section class="ad-form-section">
                <div class="ad-section-head">
                    <span class="ad-section-icon"><i class="fas fa-align-left"></i></span>
                    <div><h3>Описание и контент</h3><p>Описание, характеристики и применение по языкам.</p></div>
                </div>
                <div data-langtabs>
                    <div class="ad-langtabs">
                        <button type="button" class="ad-langtab is-active" data-lang="ru">RU</button>
                        <button type="button" class="ad-langtab" data-lang="en">EN</button>
                        <button type="button" class="ad-langtab" data-lang="kz">KZ</button>
                    </div>

                    <div class="ad-langpane is-active" data-lang="ru">
                        <div class="ad-field" wire:ignore>
                            <span>Краткое описание (RU)</span>
                            <textarea data-richtext wire:model.blur="short_description">{!! $short_description ?? '' !!}</textarea>
                        </div>
                        <div class="ad-field" wire:ignore>
                            <span>Полное описание (RU)</span>
                            <textarea data-richtext wire:model.blur="description">{!! $description ?? '' !!}</textarea>
                        </div>
                        <div class="ad-field" wire:ignore>
                            <span>Характеристики (RU)</span>
                            <textarea data-richtext wire:model.blur="char">{!! $char ?? '' !!}</textarea>
                        </div>
                        <div class="ad-field" wire:ignore>
                            <span>Применение (RU)</span>
                            <textarea data-richtext wire:model.blur="usage">{!! $usage ?? '' !!}</textarea>
                        </div>
                    </div>

                    <div class="ad-langpane" data-lang="en">
                        <label class="ad-field"><span>Название (EN)</span><input type="text" wire:model="name_en"></label>
                        <div class="ad-field" wire:ignore>
                            <span>Краткое описание (EN)</span>
                            <textarea data-richtext wire:model.blur="short_description_en">{!! $short_description_en ?? '' !!}</textarea>
                        </div>
                        <div class="ad-field" wire:ignore>
                            <span>Полное описание (EN)</span>
                            <textarea data-richtext wire:model.blur="description_en">{!! $description_en ?? '' !!}</textarea>
                        </div>
                        <div class="ad-field" wire:ignore>
                            <span>Характеристики (EN)</span>
                            <textarea data-richtext wire:model.blur="char_en">{!! $char_en ?? '' !!}</textarea>
                        </div>
                        <div class="ad-field" wire:ignore>
                            <span>Применение (EN)</span>
                            <textarea data-richtext wire:model.blur="usage_en">{!! $usage_en ?? '' !!}</textarea>
                        </div>
                    </div>

                    <div class="ad-langpane" data-lang="kz">
                        <label class="ad-field"><span>Название (KZ)</span><input type="text" wire:model="name_kz"></label>
                        <div class="ad-field" wire:ignore>
                            <span>Краткое описание (KZ)</span>
                            <textarea data-richtext wire:model.blur="short_description_kz">{!! $short_description_kz ?? '' !!}</textarea>
                        </div>
                        <div class="ad-field" wire:ignore>
                            <span>Полное описание (KZ)</span>
                            <textarea data-richtext wire:model.blur="description_kz">{!! $description_kz ?? '' !!}</textarea>
                        </div>
                        <div class="ad-field" wire:ignore>
                            <span>Характеристики (KZ)</span>
                            <textarea data-richtext wire:model.blur="char_kz">{!! $char_kz ?? '' !!}</textarea>
                        </div>
                        <div class="ad-field" wire:ignore>
                            <span>Применение (KZ)</span>
                            <textarea data-richtext wire:model.blur="usage_kz">{!! $usage_kz ?? '' !!}</textarea>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Цена и наличие --}}
            <section class="ad-form-section">
                <div class="ad-section-head">
                    <span class="ad-section-icon"><i class="fas fa-tag"></i></span>
                    <div><h3>Цена и наличие</h3><p>Нужны для Product-разметки и расширенных сниппетов. Без цены Offer невалиден.</p></div>
                </div>
                <label class="ad-check-field" style="margin-bottom:14px;">
                    <input type="checkbox" wire:model.live="price_on_request">
                    <span>Цена по запросу (скрыть цену)</span>
                </label>
                <div class="ad-fields-grid three" @if($price_on_request) style="opacity:.5;pointer-events:none;" @endif>
                    <label class="ad-field"><span>Цена</span><input type="number" step="0.01" wire:model="price" placeholder="0.00"></label>
                    <label class="ad-field"><span>Старая цена</span><input type="number" step="0.01" wire:model="old_price"></label>
                    <label class="ad-field">
                        <span>Валюта</span>
                        <select wire:model="currency">
                            <option value="KZT">KZT ₸</option><option value="USD">USD $</option>
                            <option value="EUR">EUR €</option><option value="RUB">RUB ₽</option>
                        </select>
                    </label>
                </div>
                <div class="ad-fields-grid three" style="margin-top:14px;">
                    <label class="ad-field">
                        <span>Наличие</span>
                        <select wire:model="availability">
                            <option value="InStock">В наличии</option>
                            <option value="OutOfStock">Нет в наличии</option>
                            <option value="PreOrder">Предзаказ</option>
                            <option value="BackOrder">Под заказ</option>
                        </select>
                    </label>
                    <label class="ad-field"><span>GTIN / штрихкод</span><input type="text" wire:model="gtin"></label>
                    <label class="ad-field"><span>MPN (артикул произв.)</span><input type="text" wire:model="mpn"></label>
                </div>
            </section>

            {{-- Изображения --}}
            <section class="ad-form-section">
                <div class="ad-section-head">
                    <span class="ad-section-icon"><i class="fas fa-image"></i></span>
                    <div><h3>Изображения</h3><p>Главное фото, галерея и отдельная картинка для соцсетей (OG).</p></div>
                </div>
                <div class="ad-fields-grid two">
                    <div class="ad-field">
                        <span>Главное фото</span>
                        <div class="ad-upload">
                            <input type="file" class="ad-upload__input" id="up-main" wire:model="image" accept="image/*">
                            @if($image)
                                <div class="ad-upload__preview"><img src="{{ $image->temporaryUrl() }}" alt=""></div>
                            @elseif(!empty($existing_image))
                                <div class="ad-upload__preview"><img src="{{ file_exists(public_path('assets/images/products/'.$existing_image)) ? asset('assets/images/products/'.$existing_image) : asset('assets/images/products/'.$existing_image) }}" alt=""></div>
                            @endif
                            <label for="up-main" class="ad-upload__zone">
                                <div class="ad-upload__icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                <div class="ad-upload__text"><strong>Загрузить фото</strong><span>JPG, PNG, WebP до 5 МБ</span></div>
                            </label>
                            <div wire:loading wire:target="image" class="ad-counter">Загрузка...</div>
                        </div>
                    </div>
                    <div class="ad-field">
                        <span>OG-картинка (соцсети, 1200×630)</span>
                        <div class="ad-upload">
                            <input type="file" class="ad-upload__input" id="up-og" wire:model="og_image" accept="image/*">
                            @if($og_image)
                                <div class="ad-upload__preview"><img src="{{ $og_image->temporaryUrl() }}" alt=""></div>
                            @elseif(!empty($existing_og_image))
                                <div class="ad-upload__preview"><img src="{{ file_exists(public_path('assets/images/products/'.$existing_og_image)) ? asset('assets/images/products/'.$existing_og_image) : asset('assets/images/products/'.$existing_og_image) }}" alt=""></div>
                            @endif
                            <label for="up-og" class="ad-upload__zone">
                                <div class="ad-upload__icon"><i class="fas fa-share-alt"></i></div>
                                <div class="ad-upload__text"><strong>Загрузить OG</strong><span>Необязательно</span></div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="ad-field" style="margin-top:16px;">
                    <span>Галерея</span>
                    <div class="ad-gallery">
                        @foreach($gallery as $i => $g)
                            <div class="ad-gallery__item" wire:key="gal-{{ $i }}">
                                <img src="{{ file_exists(public_path('assets/images/products/'.$g)) ? asset('assets/images/products/'.$g) : asset('assets/images/products/'.$g) }}" alt="">
                                <button type="button" class="ad-gallery__remove" wire:click="removeGallery({{ $i }})"><i class="fas fa-times"></i></button>
                            </div>
                        @endforeach
                        @foreach($galleryUploads as $i => $gu)
                            <div class="ad-gallery__item" wire:key="galu-{{ $i }}"><img src="{{ $gu->temporaryUrl() }}" alt=""></div>
                        @endforeach
                    </div>
                    <label for="up-gal" class="ad-upload__zone" style="margin-top:10px;">
                        <div class="ad-upload__icon"><i class="fas fa-images"></i></div>
                        <div class="ad-upload__text"><strong>Добавить в галерею</strong><span>Можно выбрать несколько</span></div>
                    </label>
                    <input type="file" class="ad-upload__input" id="up-gal" wire:model="galleryUploads" accept="image/*" multiple>
                    <div wire:loading wire:target="galleryUploads" class="ad-counter">Загрузка...</div>
                </div>
            </section>

            {{-- Документы --}}
            <section class="ad-form-section">
                <div class="ad-section-head">
                    <span class="ad-section-icon"><i class="fas fa-file-pdf"></i></span>
                    <div><h3>Документы и даташиты</h3><p>PDF-спецификации, сертификаты, инструкции.</p></div>
                </div>
                @if(count($documents))
                    <div class="ad-repeater" style="margin-bottom:12px;">
                        @foreach($documents as $i => $doc)
                            <div class="ad-repeater__item" style="grid-template-columns:42px 1fr 40px;" wire:key="doc-{{ $i }}">
                                <div class="ad-repeater__num"><i class="fas fa-file-pdf"></i></div>
                                <div class="ad-field" style="justify-content:center;"><strong>{{ $doc['name'] ?? $doc['file'] }}</strong></div>
                                <button type="button" class="ad-repeater__remove" wire:click="removeDocument({{ $i }})"><i class="fas fa-times"></i></button>
                            </div>
                        @endforeach
                    </div>
                @endif
                <label for="up-doc" class="ad-upload__zone">
                    <div class="ad-upload__icon"><i class="fas fa-file-upload"></i></div>
                    <div class="ad-upload__text"><strong>Загрузить документы</strong><span>PDF, DOCX до 5 МБ</span></div>
                </label>
                <input type="file" class="ad-upload__input" id="up-doc" wire:model="documentUploads" multiple>
                <div wire:loading wire:target="documentUploads" class="ad-counter">Загрузка...</div>
            </section>

            {{-- FAQ --}}
            @include('admin.partials.faq', ['property' => 'faq'])

            {{-- SEO --}}
            @include('admin.partials.seo')
        </div>

        {{-- Боковая колонка --}}
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
                            <option value="0">Опубликован</option>
                            <option value="1">Черновик / скрыт</option>
                        </select>
                    </label>
                    <label class="ad-field"><span>Порядок сортировки</span><input type="number" wire:model="sort_order" min="0"></label>
                    <label class="ad-field"><span>Видео (YouTube URL)</span><input type="text" wire:model="video_url" placeholder="https://youtu.be/..."></label>
                    <button type="submit" class="ad-btn ad-btn-primary" style="width:100%;">
                        <i class="fas fa-save"></i> Сохранить
                    </button>
                </div>
            </section>

            <section class="ad-side-card ad-form-section">
                <div class="ad-section-head mb-0">
                    <span class="ad-section-icon" style="background:linear-gradient(135deg,#16a34a,#34d399);"><i class="fas fa-lightbulb"></i></span>
                    <div><h3>SEO-подсказки</h3></div>
                </div>
                <p style="color:var(--admin-muted);font-size:.85rem;line-height:1.6;margin-top:12px;">
                    Заполняйте Meta Title (до 60 знаков) и Description (до 160) на всех 3 языках.
                    Цена и наличие нужны для расширенных сниппетов товара. FAQ повышает CTR в выдаче.
                </p>
            </section>
        </div>
    </div>
</form>
