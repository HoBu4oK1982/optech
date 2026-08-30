<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FrontendController;
use App\Http\Controllers\Api\SearchController;




Route::post('setOrder', [FrontendController::class, 'setOrder']);
Route::get('getSettings', [FrontendController::class, 'getSettings']);
Route::get('getLicenses', [FrontendController::class, 'getLicenses']);
Route::get('getSlider', [FrontendController::class, 'getSlider']);
Route::get('getPartners', [FrontendController::class, 'getPartners']);
Route::post('setPrice', [FrontendController::class, 'setPrice']);
Route::get('categories', [FrontendController::class, 'getAllCategories']);
Route::get('catalog/{category_slug}', [FrontendController::class, 'getCategory']);
Route::get('getSubCategory/{category_slug}/{subcategory_slug}', [FrontendController::class, 'getSubCategory']);
Route::get('getSubSubCategory/{category_slug}/{subcategory_slug}/{subsubcategory_slug}', [FrontendController::class, 'getSubSubCategory']);
Route::get('getProductsByCategory/{category_slug}', [FrontendController::class, 'getProductsByCategory']);
Route::get('getProduct/{product_slug}', [FrontendController::class, 'getProduct']);
Route::get('brands', [FrontendController::class, 'getBrands']);
Route::get('brand/{brand_slug}', [FrontendController::class, 'getOneBrand']);
Route::get('searchresult/{searchWord}', [FrontendController::class, 'getProductsForSearch']);
Route::get('projects', [FrontendController::class, 'getProjects']);
Route::get('project/{project_slug}', [FrontendController::class, 'getProject']);
Route::get('articles', [FrontendController::class, 'getArticles']);
Route::get('article/{article_slug}', [FrontendController::class, 'getArticle']);
Route::get('services', [FrontendController::class, 'getServices']);
Route::get('service/{service_slug}', [FrontendController::class, 'getService']);
Route::get('offers', [FrontendController::class, 'getOffers']);
Route::get('offer/{offer_slug}', [FrontendController::class, 'getOffer']);
Route::get('solcategories', [FrontendController::class, 'getSolCategories']);
Route::get('solcategory/{solcategory_slug}', [FrontendController::class, 'getSolCategory']);
Route::get('solutions/{solution_category_slug}/{solution_slug}', [FrontendController::class, 'getSolution']);

// Лёгкие выборки только для sitemap.xml (см. FrontendController)
Route::get('sitemap/categories', [FrontendController::class, 'getCategoriesForSitemap']);
Route::get('sitemap/products', [FrontendController::class, 'getProductsForSitemap']);
Route::get('sitemap/solutions', [FrontendController::class, 'getSolutionsForSitemap']);


Route::prefix('v1')->middleware(\App\Http\Middleware\SetApiLocale::class)->group(function () {

    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/search/suggest', [SearchController::class, 'suggest']);
    Route::get('/search/popular', [SearchController::class, 'popular']);
    Route::get('/search/no-results', [SearchController::class, 'noResultsList']);
    Route::post('/search/no-results', [SearchController::class, 'noResults']);
    Route::post('/search/click', [SearchController::class, 'click']);

});
