<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SearchController;


Route::get('/search', [SearchController::class, 'search']);
Route::get('/search/suggest', [SearchController::class, 'suggest']);
Route::get('/search/popular', [SearchController::class, 'popular']);
Route::get('/search/no-results', [SearchController::class, 'noResultsList']);
Route::post('/search/no-results', [SearchController::class, 'noResults']);
Route::post('/search/click', [SearchController::class, 'click']);

