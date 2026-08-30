# OPTECH.KZ — Миграция с React SPA на Next.js SSR

## Что было сделано

### Проблема
React SPA рендерит пустой HTML — поисковые роботы не видят контент.

### Решение
Next.js App Router с SSR (Server-Side Rendering) — весь контент рендерится на сервере, поисковики получают полный HTML.

## Архитектура

```
┌──────────────────────────────────┐
│  Next.js Frontend (SSR)          │
│  optech.kz (port 3000)          │
│  ├── SSR страницы                │
│  ├── ISR (revalidate: 60s)       │
│  ├── SEO meta tags               │
│  ├── JSON-LD structured data     │
│  ├── Sitemap.xml                 │
│  └── i18n (ru/en/kz)            │
└────────────┬─────────────────────┘
             │ API calls
             ▼
┌──────────────────────────────────┐
│  Laravel Backend (API + Admin)   │
│  optech.kz:8000                 │
│  ├── /api/* — REST API           │
│  ├── /login — Админ-панель       │
│  └── Livewire CRUD               │
└──────────────────────────────────┘
```

## Структура проекта Next.js

```
optech-frontend/
├── src/
│   ├── app/
│   │   ├── [locale]/                    # i18n routing (ru/en/kz)
│   │   │   ├── layout.tsx               # SSR layout (Header/Footer)
│   │   │   ├── page.tsx                 # Главная
│   │   │   ├── catalog/                 # Каталог
│   │   │   │   ├── page.tsx             # Все категории
│   │   │   │   └── [category_slug]/     # Категория → подкатегории → продукты
│   │   │   ├── product/[product_slug]/  # Карточка товара
│   │   │   ├── brands/                  # Бренды
│   │   │   ├── brand/[brand_slug]/      # Товары бренда
│   │   │   ├── articles/                # Новости
│   │   │   ├── article/[article_slug]/  # Новость
│   │   │   ├── projects/                # Проекты
│   │   │   ├── services/                # Услуги
│   │   │   ├── offers/                  # Спец. предложения
│   │   │   ├── solutions/               # Решения
│   │   │   ├── search/[searchWord]/     # Поиск
│   │   │   ├── contacts/               # Контакты
│   │   │   └── about/                  # О компании
│   │   ├── sitemap.ts                  # Автогенерация sitemap.xml
│   │   └── robots.ts                   # robots.txt
│   ├── components/                     # Компоненты
│   ├── lib/api.ts                      # API клиент
│   ├── i18n/translations.ts            # Переводы
│   └── middleware.ts                   # i18n redirect
├── .env.local                          # Переменные окружения
└── next.config.js                      # Конфигурация Next.js
```

## SEO улучшения

1. **SSR** — весь HTML рендерится на сервере, роботы видят контент
2. **Meta теги** — `generateMetadata()` на каждой странице (title, description, keywords, OpenGraph)
3. **JSON-LD** — структурированные данные (Organization, Product, Article, BreadcrumbList)
4. **Sitemap.xml** — автоматическая генерация из базы данных
5. **robots.txt** — правильные правила индексации
6. **Breadcrumbs** — с JSON-LD микроразметкой
7. **i18n** — `hreflang` теги для мультиязычности
8. **ISR** — страницы обновляются каждые 60 секунд без пересборки

## Деплой

### 1. Настройка .env.local

```bash
cp .env.example .env.local
# Отредактировать URLs:
# NEXT_PUBLIC_API_URL=https://optech.kz:8000/api
# NEXT_PUBLIC_STORAGE_URL=https://optech.kz:8000
# NEXT_PUBLIC_SITE_URL=https://optech.kz
```

### 2. Установка и запуск

```bash
npm install
npm run build
npm start
```

### 3. CORS на Laravel

В `config/cors.php` добавить домен Next.js:

```php
'allowed_origins' => ['https://optech.kz', 'http://localhost:3000'],
```

### 4. Nginx конфигурация

```nginx
# Next.js frontend
server {
    listen 80;
    server_name optech.kz;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

# Laravel backend (API + Admin)
server {
    listen 8000;
    server_name optech.kz;
    root /var/www/laravel/public;
    
    # ... стандартный конфиг Laravel
}
```

### 5. PM2 (для production)

```bash
npm install -g pm2
pm2 start npm --name "optech-next" -- start
pm2 save
pm2 startup
```

## Что НЕ изменилось

- Laravel админ-панель (Livewire) — работает как и раньше на :8000
- API endpoints — все те же, фронтенд их вызывает через SSR
- База данных — без изменений
- Загрузка файлов/изображений — через Laravel Storage как раньше

## Дальнейшие улучшения

- [ ] Добавить `generateStaticParams()` для ISG ключевых страниц
- [ ] Настроить `next/image` с оптимизацией изображений
- [ ] Добавить кэширование API ответов
- [ ] PWA манифест
- [ ] Google Analytics / Yandex.Metrika через `next/script`
- [ ] Open Graph изображения для каждой страницы
