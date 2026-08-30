@section('title', 'Настройки и SEO — OPTECH')
@section('page-title', 'Настройки и SEO')

<div>
    <form wire:submit="save">
        <div class="ad-form-actions-top">
            <div></div>
            <button type="submit" class="ad-btn ad-btn-primary">
                <span wire:loading.remove wire:target="save"><i class="fas fa-save"></i> Сохранить настройки</span>
                <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin"></i> Сохранение...</span>
            </button>
        </div>

        <div class="ad-form-layout">
            <div class="ad-form-main">
                {{-- Организация --}}
                <section class="ad-form-section">
                    <div class="ad-section-head"><span class="ad-section-icon"><i class="fas fa-building"></i></span><div><h3>Организация</h3><p>Данные для schema.org Organization / LocalBusiness.</p></div></div>
                    <div class="ad-fields-grid two">
                        <label class="ad-field"><span>Название сайта</span><input type="text" wire:model="site_name"></label>
                        <label class="ad-field"><span>Юр. название</span><input type="text" wire:model="org_legal_name" placeholder="ТОО «...»"></label>
                        <label class="ad-field"><span>БИН</span><input type="text" wire:model="bin"></label>
                        <label class="ad-field"><span>Слоган</span><input type="text" wire:model="slogan"></label>
                        <label class="ad-field"><span>Телефон</span><input type="text" wire:model="phone"></label>
                        <label class="ad-field"><span>Городской телефон</span><input type="text" wire:model="city_phone"></label>
                        <label class="ad-field"><span>Email</span><input type="email" wire:model="email">@error('email')<span class="ad-field-error">{{ $message }}</span>@enderror</label>
                        <label class="ad-field"><span>Время работы</span><input type="text" wire:model="work_time"></label>
                        <label class="ad-field span-2"><span>Адрес</span><input type="text" wire:model="address"></label>
                        <label class="ad-field"><span>Гео-широта (lat)</span><input type="text" wire:model="geo_lat" placeholder="43.238"></label>
                        <label class="ad-field"><span>Гео-долгота (lng)</span><input type="text" wire:model="geo_lng" placeholder="76.889"></label>
                        <label class="ad-field span-2"><span>Карта (iframe/URL)</span><input type="text" wire:model="map"></label>
                    </div>
                </section>

                {{-- Соцсети --}}
                <section class="ad-form-section">
                    <div class="ad-section-head mb-0" style="display:flex;justify-content:space-between;width:100%;">
                        <div style="display:flex;gap:14px;"><span class="ad-section-icon"><i class="fas fa-share-nodes"></i></span><div><h3>Соцсети (sameAs)</h3><p>Ссылки на профили — попадают в schema.org.</p></div></div>
                        <button type="button" class="ad-btn ad-btn-sm" wire:click="addRepeaterItem('social_links', { network: '', url: '' })"><i class="fas fa-plus"></i> Добавить</button>
                    </div>
                    <div class="ad-repeater" style="margin-top:16px;">
                        @forelse($social_links as $i => $l)
                            <div class="ad-repeater__item" wire:key="sl-{{ $i }}">
                                <div class="ad-repeater__num">{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</div>
                                <div class="ad-repeater__fields" style="grid-template-columns:1fr 2fr;">
                                    <label class="ad-field"><span>Сеть</span><input type="text" wire:model="social_links.{{ $i }}.network" placeholder="Instagram"></label>
                                    <label class="ad-field"><span>URL</span><input type="text" wire:model="social_links.{{ $i }}.url" placeholder="https://..."></label>
                                </div>
                                <button type="button" class="ad-repeater__remove" wire:click="removeRepeaterItem('social_links', {{ $i }})"><i class="fas fa-times"></i></button>
                            </div>
                        @empty
                            <div class="ad-empty" style="padding:24px;"><i class="fas fa-share-nodes"></i>Ссылок пока нет.</div>
                        @endforelse
                    </div>
                </section>

                {{-- Дефолтные мета --}}
                <section class="ad-form-section">
                    <div class="ad-section-head"><span class="ad-section-icon"><i class="fas fa-search"></i></span><div><h3>Шаблоны мета по умолчанию</h3><p>Используются, если на странице мета не задана.</p></div></div>
                    <div data-langtabs>
                        <div class="ad-langtabs"><button type="button" class="ad-langtab is-active" data-lang="ru">RU</button><button type="button" class="ad-langtab" data-lang="en">EN</button><button type="button" class="ad-langtab" data-lang="kz">KZ</button></div>
                        <div class="ad-langpane is-active" data-lang="ru"><label class="ad-field"><span>Default Title (RU)</span><input type="text" wire:model="default_meta_title"></label><label class="ad-field"><span>Default Description (RU)</span><textarea rows="2" wire:model="default_meta_description"></textarea></label></div>
                        <div class="ad-langpane" data-lang="en"><label class="ad-field"><span>Default Title (EN)</span><input type="text" wire:model="default_meta_title_en"></label><label class="ad-field"><span>Default Description (EN)</span><textarea rows="2" wire:model="default_meta_description_en"></textarea></label></div>
                        <div class="ad-langpane" data-lang="kz"><label class="ad-field"><span>Default Title (KZ)</span><input type="text" wire:model="default_meta_title_kz"></label><label class="ad-field"><span>Default Description (KZ)</span><textarea rows="2" wire:model="default_meta_description_kz"></textarea></label></div>
                    </div>
                </section>

                {{-- Аналитика --}}
                <section class="ad-form-section">
                    <div class="ad-section-head"><span class="ad-section-icon" style="background:linear-gradient(135deg,#16a34a,#34d399);"><i class="fas fa-chart-line"></i></span><div><h3>Аналитика и верификации</h3></div></div>
                    <div class="ad-fields-grid two">
                        <label class="ad-field"><span>Google Analytics 4 (G-...)</span><input type="text" wire:model="ga4_id"></label>
                        <label class="ad-field"><span>Google Tag Manager (GTM-...)</span><input type="text" wire:model="gtm_id"></label>
                        <label class="ad-field"><span>Яндекс.Метрика ID</span><input type="text" wire:model="yandex_metrika_id"></label>
                        <label class="ad-field"><span>Язык по умолчанию</span><select wire:model="default_locale"><option value="ru">RU</option><option value="en">EN</option><option value="kz">KZ</option></select></label>
                        <label class="ad-field"><span>Google verification</span><input type="text" wire:model="google_verification"></label>
                        <label class="ad-field"><span>Yandex verification</span><input type="text" wire:model="yandex_verification"></label>
                        <label class="ad-field span-2"><span>robots.txt — доп. правила</span><textarea rows="3" wire:model="robots_extra" placeholder="Disallow: /cart"></textarea></label>
                    </div>
                </section>
            </div>

            <div class="ad-form-side">
                <section class="ad-form-section">
                    <div class="ad-section-head mb-0"><span class="ad-section-icon"><i class="fas fa-image"></i></span><div><h3>Логотип и OG</h3></div></div>
                    <div class="ad-fields-grid" style="margin-top:16px;">
                        <div class="ad-field"><span>Логотип</span><div class="ad-upload"><input type="file" class="ad-upload__input" id="set-logo" wire:model="logo" accept="image/*">@if($logo)<div class="ad-upload__preview"><img src="{{ $logo->temporaryUrl() }}"></div>@elseif(!empty($existing_logo))@php($logoUrl = file_exists(public_path('storage/settings/'.$existing_logo)) ? asset('assets/images/settings/'.$existing_logo) : (file_exists(public_path('assets/images/settings/'.$existing_logo)) ? asset('assets/images/settings/'.$existing_logo) : (file_exists(public_path('uploads/images/'.$existing_logo)) ? asset('uploads/images/'.$existing_logo) : asset('assets/images/settings/'.$existing_logo))))<div class="ad-upload__preview"><img src="{{ $logoUrl }}" onerror="this.style.display='none'"></div>@endif<label for="set-logo" class="ad-upload__zone"><div class="ad-upload__icon"><i class="fas fa-cloud-upload-alt"></i></div><div class="ad-upload__text"><strong>Загрузить</strong><span>SVG/PNG</span></div></label></div></div>
                        <div class="ad-field"><span>OG по умолчанию (1200×630)</span><div class="ad-upload"><input type="file" class="ad-upload__input" id="set-og" wire:model="default_og_image" accept="image/*">@if($default_og_image)<div class="ad-upload__preview"><img src="{{ $default_og_image->temporaryUrl() }}"></div>@elseif(!empty($existing_og))@php($ogUrl = file_exists(public_path('storage/settings/'.$existing_og)) ? asset('assets/images/settings/'.$existing_og) : (file_exists(public_path('assets/images/settings/'.$existing_og)) ? asset('assets/images/settings/'.$existing_og) : (file_exists(public_path('uploads/images/'.$existing_og)) ? asset('uploads/images/'.$existing_og) : asset('assets/images/settings/'.$existing_og))))<div class="ad-upload__preview"><img src="{{ $ogUrl }}" onerror="this.style.display='none'"></div>@endif<label for="set-og" class="ad-upload__zone"><div class="ad-upload__icon"><i class="fas fa-share-alt"></i></div><div class="ad-upload__text"><strong>Загрузить</strong><span>для соцсетей</span></div></label></div></div>
                        <button type="submit" class="ad-btn ad-btn-primary" style="width:100%;"><i class="fas fa-save"></i> Сохранить</button>
                    </div>
                </section>
            </div>
        </div>
    </form>
</div>
