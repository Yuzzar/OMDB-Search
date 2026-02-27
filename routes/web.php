<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to movies or login
Route::get('/', function () {
    return redirect()->route('movies.index');
});

// -----------------------------------------------------------------------
// Authentication Routes
// -----------------------------------------------------------------------
Route::get('/login', 'AuthController@showLogin')->name('login');
Route::post('/login', 'AuthController@login')->name('login.submit');
Route::post('/logout', 'AuthController@logout')->name('logout');

// -----------------------------------------------------------------------
// Language Switcher
// -----------------------------------------------------------------------
Route::post('/locale', function (\Illuminate\Http\Request $request) {
    $locale = $request->input('locale', 'en');
    $available = ['en', 'id'];

    if (in_array($locale, $available)) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }

    return back();
})->name('locale.switch');

// -----------------------------------------------------------------------
// Protected Routes (require login)
// -----------------------------------------------------------------------
Route::middleware('auth.check')->group(function () {

    // Movies
    Route::get('/movies', 'MovieController@index')->name('movies.index');
    Route::get('/movies/load-more', 'MovieController@loadMore')->name('movies.load-more');
    Route::get('/movies/{imdbId}', 'MovieController@detail')->name('movies.detail');

    // Favorites
    Route::get('/favorites', 'FavoriteController@index')->name('favorites.index');
    Route::post('/favorites', 'FavoriteController@store')->name('favorites.store');
    Route::delete('/favorites/{imdbId}', 'FavoriteController@destroy')->name('favorites.destroy');
});

