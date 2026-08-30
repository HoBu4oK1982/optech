<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Observers\SearchableObserver;
use App\Models\Product;
use App\Models\Category;
use App\Models\Article;
use App\Models\Solution;
use App\Models\SolutionCategory;
use App\Models\Project;

/**
 * Регистрирует observers, поддерживающие поисковый индекс в актуальном
 * состоянии при сохранении/удалении сущностей.
 *
 * Подключение: добавьте App\Providers\SearchServiceProvider::class
 * в bootstrap/providers.php (Laravel 11/12) или config/app.php (Laravel 10).
 */
class SearchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ([Product::class, Category::class, Article::class, Solution::class, SolutionCategory::class, Project::class] as $model) {
            $model::observe(SearchableObserver::class);
        }
    }
}
