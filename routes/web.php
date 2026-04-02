<?php

use Illuminate\Support\Facades\Route;

// Use Route::redirect instead of a Closure for better performance and caching
Route::redirect('/', '/movies')->middleware('auth.check');

Route::get('/login', 'AuthController@showLogin')->name('login');
Route::post('/login', 'AuthController@login')->name('login.submit');
Route::post('/logout', 'AuthController@logout')->name('logout');

// Moved locale logic to AuthController@switchLocale for route caching compatibility
Route::post('/locale', 'AuthController@switchLocale')->name('locale.switch');

Route::middleware('auth.check')->group(function () {
    Route::get('/movies', 'MovieController@index')->name('movies.index');
    Route::get('/movies/load-more', 'MovieController@loadMore')->name('movies.load-more');
    Route::get('/movies/{imdbId}', 'MovieController@detail')->name('movies.detail');

    Route::get('/favorites', 'FavoriteController@index')->name('favorites.index');
    Route::post('/favorites', 'FavoriteController@store')->name('favorites.store');
    Route::delete('/favorites/{imdbId}', 'FavoriteController@destroy')->name('favorites.destroy');
});
