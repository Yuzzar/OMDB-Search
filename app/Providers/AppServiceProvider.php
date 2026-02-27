<?php

namespace App\Providers;

use App\Repositories\Contracts\FavoriteRepositoryInterface;
use App\Repositories\FavoriteRepository;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Bind FavoriteRepository interface to implementation
        $this->app->bind(FavoriteRepositoryInterface::class, FavoriteRepository::class);

        // Register GuzzleHTTP client as singleton
        $this->app->singleton(Client::class, function ($app) {
            return new Client([
                'timeout'         => 10,
                'connect_timeout' => 5,
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

