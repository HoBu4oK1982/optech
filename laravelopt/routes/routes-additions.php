<?php

/*
|--------------------------------------------------------------------------
| ДОБАВИТЬ в routes/web.php
|--------------------------------------------------------------------------
| Внутри существующей группы middleware ['auth:sanctum','verified']
| (рядом с остальными админ-маршрутами) добавьте маршрут менеджера редиректов.
| Остальные сущности (articles, solcategories, solutions, projects, settings)
| УЖЕ имеют маршруты — их компоненты просто заменяются новыми версиями.
*/

use App\Livewire\RedirectsComponent;

Route::get('/redirects', RedirectsComponent::class)->name('redirects'); // Менеджер 301/302

/*
| (Опционально) Применение редиректов на фронте Laravel:
| зарегистрируйте App\Http\Middleware\ApplyRedirects в группе 'web'.
| Для Next.js-фронта редиректы обычно читаются из таблицы redirects через API.
*/
