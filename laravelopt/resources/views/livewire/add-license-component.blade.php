@section('title', 'Новая лицензия — OPTECH')
@section('page-title', 'Новая лицензия')
<div>
    <div class="ad-page-head"><div><h2>Создание лицензии</h2></div><a wire:navigate href="{{ route('licenses') }}" class="ad-btn"><i class="fas fa-arrow-left"></i> Все лицензии</a></div>
    <div class="ad-panel-shell">
        <div class="ad-form-grid" style="max-width:640px;">
            <label class="ad-field">
                <span>Тип лицензии</span>
                <select wire:model="type">
                    <option value="0">Выберите тип</option>
                    <option value="1">Лицензия</option>
                    <option value="2">Сертификат ИСО</option>
                    <option value="3">Авторизационное письмо</option>
                </select>
                @error('type')<span class="ad-field-error">{{ $message }}</span>@enderror
            </label>

            <div class="ad-field"><span>Изображение</span>
                <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;">
                    <label class="ad-file" x-data="{n:''}"><i class="fas fa-upload"></i><span x-text="n || 'Выбрать изображение'"></span><input type="file" wire:model="image" x-on:change="n = ($event.target.files[0] && $event.target.files[0].name) || ''"></label>
                    <span class="ad-counter">1240×1750 px</span>
                    <span wire:loading wire:target="image" class="ad-counter">Загрузка…</span>
                </div>
                @error('image')<span class="ad-field-error">{{ $message }}</span>@enderror
                @if ($image)<div style="margin-top:10px;"><img src="{{ $image->temporaryUrl() }}" class="ad-thumb" style="width:140px;height:auto;object-fit:contain;background:#fff;"></div>@endif
            </div>

            <label class="ad-field"><span>ALT (описание изображения)</span><input type="text" wire:model="alt" placeholder="Напр.: Лицензия на монтаж ВОЛС"></label>
        </div>
        <div style="margin-top:18px;"><button class="ad-btn ad-btn-primary" wire:click="addSolution"><i class="fas fa-save"></i> Сохранить</button></div>
    </div>
</div>
