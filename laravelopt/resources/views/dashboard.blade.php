@extends('layouts.admin')

@section('title', 'Дашборд — OPTECH')
@section('page-title', 'Дашборд')

@section('content')
<style>
    .optech-welcome { text-align: center; padding: 48px 20px; }
    .optech-welcome-logo { width: 200px; height: auto; margin-bottom: 24px; color: var(--admin-text); }
    .optech-welcome h2 { font-size: 1.8rem; font-weight: 800; letter-spacing: -0.03em; margin-bottom: 8px; color: var(--admin-text); }
    .optech-welcome p { color: var(--admin-muted); font-size: 1rem; margin-bottom: 40px; }

    .optech-quick-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 16px; max-width: 900px; margin: 0 auto;
    }
    .optech-quick-card {
        display: flex; align-items: center; gap: 14px;
        padding: 18px 20px; border-radius: 16px;
        background: var(--admin-card-bg);
        border: 1px solid var(--admin-border);
        box-shadow: var(--admin-card-shadow);
        transition: all 0.25s ease;
        text-decoration: none !important; color: var(--admin-text) !important;
    }
    .optech-quick-card:hover {
        transform: translateY(-3px);
        border-color: rgba(59,130,246,0.3);
        box-shadow: 0 8px 28px rgba(59,130,246,0.12);
    }
    .optech-quick-card-icon {
        width: 44px; height: 44px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-size: 1.1rem;
    }
    .optech-quick-card-icon.blue { background: rgba(59,130,246,0.14); color: #2563eb; }
    .optech-quick-card-icon.green { background: rgba(34,197,94,0.14); color: #16a34a; }
    .optech-quick-card-icon.violet { background: rgba(139,92,246,0.14); color: #7c3aed; }
    .optech-quick-card-icon.orange { background: rgba(245,158,11,0.14); color: #d97706; }
    .optech-quick-card-icon.cyan { background: rgba(6,182,212,0.14); color: #0891b2; }
    .optech-quick-card-icon.rose { background: rgba(244,63,94,0.14); color: #e11d48; }
    .optech-quick-card-icon.amber { background: rgba(217,119,6,0.14); color: #d97706; }

    .optech-quick-card-text { line-height: 1.2; }
    .optech-quick-card-title { font-weight: 700; font-size: 0.92rem; }
    .optech-quick-card-meta { font-size: 0.78rem; color: var(--admin-muted); margin-top: 2px; }
</style>

<div class="optech-welcome">
    <svg class="optech-welcome-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 481.59 94.28">
        <g>
            <path fill="currentColor" d="M47,94.28A46.82,46.82,0,0,0,94.15,47.14,46.74,46.74,0,0,0,47,0,46.63,46.63,0,0,0,0,47.14,46.71,46.71,0,0,0,47,94.28ZM47,76.8c-16.45,0-29.27-12.31-29.27-29.66S30.56,17.35,47,17.35,76.28,29.66,76.28,47.14,63.46,76.8,47,76.8Z"/>
            <path fill="currentColor" d="M140.5,1.81H106.7V92.47h17.87V62.68H140.5c17.48,0,30.95-13.47,30.95-30.43S158,1.81,140.5,1.81Zm0,44.16H124.57V18.52H140.5c7.64,0,13.21,5.83,13.21,13.73S148.14,46,140.5,46Z"/>
            <path fill="currentColor" d="M241.38,1.81h-66.7v17.1H199V92.47H216.9V18.91h24.48Z"/>
            <path fill="currentColor" d="M362.32,94.28c16.71,0,31.34-8.42,39-21.37L385.89,64c-4.27,7.9-13.21,12.83-23.57,12.83-17.74,0-29.39-12.31-29.39-29.66s11.65-29.79,29.39-29.79c10.36,0,19.17,4.92,23.57,13l15.41-8.93C393.53,8.42,378.9,0,362.32,0c-27.45,0-47.14,20.59-47.14,47.14S334.87,94.28,362.32,94.28Z"/>
            <path fill="currentColor" d="M463.85,1.81v36H430.17v-36H412.3V92.47h17.87V54.91h33.68V92.47h17.74V1.81Z"/>
            <path fill="#4eb0fb" fill-rule="evenodd" d="M252.79,1.77h39V19h-39Z"/>
            <path fill="#4eb0fb" fill-rule="evenodd" d="M252.79,37.77h48V55h-48Z"/>
            <path fill="#4eb0fb" fill-rule="evenodd" d="M252.79,75.26H309V92.51H252.79Z"/>
        </g>
    </svg>

    <h2>Добро пожаловать в панель управления</h2>
    <p>Управляйте контентом сайта optech.kz — каталог, новости, проекты и настройки</p>

    <div class="optech-quick-grid">
        <a wire:navigate href="{{ route('categories') }}" class="optech-quick-card">
            <div class="optech-quick-card-icon blue"><i class="fas fa-sitemap"></i></div>
            <div class="optech-quick-card-text">
                <div class="optech-quick-card-title">Категории</div>
                <div class="optech-quick-card-meta">Структура каталога</div>
            </div>
        </a>
        <a wire:navigate href="{{ route('products') }}" class="optech-quick-card">
            <div class="optech-quick-card-icon green"><i class="fas fa-box-open"></i></div>
            <div class="optech-quick-card-text">
                <div class="optech-quick-card-title">Продукция</div>
                <div class="optech-quick-card-meta">Товары и артикулы</div>
            </div>
        </a>
        <a wire:navigate href="{{ route('brands') }}" class="optech-quick-card">
            <div class="optech-quick-card-icon violet"><i class="fas fa-copyright"></i></div>
            <div class="optech-quick-card-text">
                <div class="optech-quick-card-title">Бренды</div>
                <div class="optech-quick-card-meta">Производители</div>
            </div>
        </a>
        <a wire:navigate href="{{ route('articles') }}" class="optech-quick-card">
            <div class="optech-quick-card-icon orange"><i class="fas fa-newspaper"></i></div>
            <div class="optech-quick-card-text">
                <div class="optech-quick-card-title">Новости</div>
                <div class="optech-quick-card-meta">Статьи и блог</div>
            </div>
        </a>
        <a wire:navigate href="{{ route('projects') }}" class="optech-quick-card">
            <div class="optech-quick-card-icon cyan"><i class="fas fa-project-diagram"></i></div>
            <div class="optech-quick-card-text">
                <div class="optech-quick-card-title">Проекты</div>
                <div class="optech-quick-card-meta">Реализованные проекты</div>
            </div>
        </a>
        <a wire:navigate href="{{ route('solutions') }}" class="optech-quick-card">
            <div class="optech-quick-card-icon rose"><i class="fas fa-lightbulb"></i></div>
            <div class="optech-quick-card-text">
                <div class="optech-quick-card-title">Решения</div>
                <div class="optech-quick-card-meta">Готовые решения</div>
            </div>
        </a>
        <a wire:navigate href="{{ route('sliders') }}" class="optech-quick-card">
            <div class="optech-quick-card-icon amber"><i class="fas fa-images"></i></div>
            <div class="optech-quick-card-text">
                <div class="optech-quick-card-title">Слайдер</div>
                <div class="optech-quick-card-meta">Главный баннер</div>
            </div>
        </a>
        <a wire:navigate href="{{ route('settings') }}" class="optech-quick-card">
            <div class="optech-quick-card-icon blue"><i class="fas fa-cog"></i></div>
            <div class="optech-quick-card-text">
                <div class="optech-quick-card-title">Настройки</div>
                <div class="optech-quick-card-meta">Контакты и SEO</div>
            </div>
        </a>
    </div>
</div>
@endsection
