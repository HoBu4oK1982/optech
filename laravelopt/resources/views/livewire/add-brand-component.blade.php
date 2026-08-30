@section('title', 'Новый бренд — OPTECH')
@section('page-title', 'Новый бренд')
<div>
    <div class="ad-page-head">
        <div><h2>Создание бренда</h2></div>
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
                        <input type="file" wire:model="image" x-on:change="n = ($event.target.files[0] && $event.target.files[0].name) || ''">
                    </label>
                    @if($image)
                        <img src="{{ $image->temporaryUrl() }}" class="ad-thumb" style="width:120px;height:auto;object-fit:contain;background:#fff;" alt="">
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

            <label class="ad-field"><span>Meta заголовок</span><input type="text" wire:model="meta_title"></label>
            <label class="ad-field"><span>Meta ключевые слова</span><textarea rows="3" wire:model="meta_keywords"></textarea></label>
            <label class="ad-field"><span>Meta описание</span><textarea rows="3" wire:model="meta_description"></textarea></label>
        </div>

        <div style="margin-top:20px;">
            <button class="ad-btn ad-btn-primary" wire:click="addSolution"><i class="fas fa-save"></i> Создать бренд</button>
        </div>
    </div>
</div>
