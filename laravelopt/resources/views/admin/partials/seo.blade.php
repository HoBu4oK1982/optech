{{--
    Переиспользуемый SEO-блок (соответствует ТЗ New Web).
    Компонент-хост ДОЛЖЕН иметь публичные свойства:
      meta_title(_en/_kz), meta_description(_en/_kz), meta_keywords(_en/_kz),
      seo_h1(_en/_kz), seo_h2,
      og_title, og_description,
      image_alt(_en/_kz), image_title,
      canonical_url, is_indexable, is_followable,
      in_sitemap, sitemap_priority, sitemap_changefreq
    Параметры: $showH1 (default true), $showSerp (default true)
--}}
@php($showH1 = $showH1 ?? true)
@php($showSerp = $showSerp ?? true)

<section class="ad-form-section">
    <div class="ad-section-head">
        <span class="ad-section-icon"><i class="fas fa-search"></i></span>
        <div>
            <h3>SEO</h3>
            <p>Meta по языкам, H1/H2, OG, alt изображений, canonical, индексация и sitemap.</p>
        </div>
    </div>

    @if($showSerp)
        <div class="ad-serp" style="margin-bottom:16px;">
            <div class="ad-serp__url">https://optech.kz/...</div>
            <div class="ad-serp__title">{{ $meta_title ?: 'Заголовок страницы появится здесь' }}</div>
            <div class="ad-serp__desc">{{ $meta_description ?: 'Описание страницы появится здесь — именно его видят пользователи в выдаче.' }}</div>
        </div>
    @endif

    <div data-langtabs>
        <div class="ad-langtabs">
            <button type="button" class="ad-langtab is-active" data-lang="ru">RU</button>
            <button type="button" class="ad-langtab" data-lang="en">EN</button>
            <button type="button" class="ad-langtab" data-lang="kz">KZ</button>
        </div>

        {{-- RU --}}
        <div class="ad-langpane is-active" data-lang="ru">
            <label class="ad-field">
                <span>Meta Title (RU)</span>
                <input type="text" name="meta_title" data-counter="60" wire:model="meta_title" placeholder="Оптоволоконное оборудование купить в Алматы | OPTECH">
                <small class="ad-counter" data-counter-for="meta_title"></small>
            </label>
            <label class="ad-field">
                <span>Meta Description (RU)</span>
                <textarea name="meta_description" rows="2" data-counter="160" wire:model="meta_description" placeholder="Краткое описание для выдачи, до 160 символов"></textarea>
                <small class="ad-counter" data-counter-for="meta_description"></small>
            </label>
            <label class="ad-field"><span>Meta Keywords (RU)</span><input type="text" wire:model="meta_keywords" placeholder="ключевое слово, ещё одно"></label>
            @if($showH1)<label class="ad-field"><span>H1 (RU)</span><input type="text" wire:model="seo_h1" placeholder="Заголовок H1 на странице"></label>@endif
            <label class="ad-field"><span>Alt главного изображения (RU)</span><input type="text" wire:model="image_alt" placeholder="Описание картинки для поиска и доступности"></label>
        </div>

        {{-- EN --}}
        <div class="ad-langpane" data-lang="en">
            <label class="ad-field"><span>Meta Title (EN)</span><input type="text" wire:model="meta_title_en"></label>
            <label class="ad-field"><span>Meta Description (EN)</span><textarea rows="2" wire:model="meta_description_en"></textarea></label>
            <label class="ad-field"><span>Meta Keywords (EN)</span><input type="text" wire:model="meta_keywords_en"></label>
            @if($showH1)<label class="ad-field"><span>H1 (EN)</span><input type="text" wire:model="seo_h1_en"></label>@endif
            <label class="ad-field"><span>Alt изображения (EN)</span><input type="text" wire:model="image_alt_en"></label>
        </div>

        {{-- KZ --}}
        <div class="ad-langpane" data-lang="kz">
            <label class="ad-field"><span>Meta Title (KZ)</span><input type="text" wire:model="meta_title_kz"></label>
            <label class="ad-field"><span>Meta Description (KZ)</span><textarea rows="2" wire:model="meta_description_kz"></textarea></label>
            <label class="ad-field"><span>Meta Keywords (KZ)</span><input type="text" wire:model="meta_keywords_kz"></label>
            @if($showH1)<label class="ad-field"><span>H1 (KZ)</span><input type="text" wire:model="seo_h1_kz"></label>@endif
            <label class="ad-field"><span>Alt изображения (KZ)</span><input type="text" wire:model="image_alt_kz"></label>
        </div>
    </div>

    <div class="ad-fields-grid two" style="margin-top:14px;">
        @if($showH1)<label class="ad-field"><span>Дополнительный заголовок H2</span><input type="text" wire:model="seo_h2" placeholder="Подзаголовок страницы (H2)"></label>@endif
        <label class="ad-field"><span>Title изображения</span><input type="text" wire:model="image_title" placeholder="title главного изображения"></label>
    </div>

    {{-- Open Graph --}}
    <div class="ad-section-head" style="margin-top:22px;">
        <span class="ad-section-icon" style="background:linear-gradient(135deg,#6d5cff,#8458ff);"><i class="fas fa-share-alt"></i></span>
        <div><h3 style="font-size:1rem;">Open Graph (соцсети)</h3><p>Если пусто — берётся из Meta Title / Description. Картинка задаётся выше (OG-изображение).</p></div>
    </div>
    <div class="ad-fields-grid">
        <label class="ad-field"><span>OG Title</span><input type="text" wire:model="og_title"></label>
        <label class="ad-field"><span>OG Description</span><textarea rows="2" wire:model="og_description"></textarea></label>
    </div>

    {{-- Индексация + canonical --}}
    <div class="ad-fields-grid two" style="margin-top:18px;">
        <label class="ad-field">
            <span>Canonical URL</span>
            <input type="text" wire:model="canonical_url" placeholder="оставьте пустым = автоматически">
        </label>
        <div style="display:grid;gap:10px;">
            <label class="ad-check-field"><input type="checkbox" wire:model="is_indexable"><span>index (разрешить индексацию)</span></label>
            <label class="ad-check-field"><input type="checkbox" wire:model="is_followable"><span>follow (переходить по ссылкам)</span></label>
        </div>
    </div>

    {{-- Sitemap --}}
    <div class="ad-fields-grid three" style="margin-top:14px;">
        <label class="ad-check-field"><input type="checkbox" wire:model="in_sitemap"><span>Включать в sitemap.xml</span></label>
        <label class="ad-field"><span>Приоритет (0.0–1.0)</span><input type="number" step="0.1" min="0" max="1" wire:model="sitemap_priority"></label>
        <label class="ad-field">
            <span>Частота обновления</span>
            <select wire:model="sitemap_changefreq">
                <option value="always">always</option>
                <option value="hourly">hourly</option>
                <option value="daily">daily</option>
                <option value="weekly">weekly</option>
                <option value="monthly">monthly</option>
                <option value="yearly">yearly</option>
                <option value="never">never</option>
            </select>
        </label>
    </div>
</section>
