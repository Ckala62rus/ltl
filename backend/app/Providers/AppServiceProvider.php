<?php

namespace App\Providers;

use App\Contracts\HoldRepositoryInterface;
use App\Contracts\SlotRepositoryInterface;
use App\Contracts\SlotServiceInterface;
use App\Repositories\HoldRepository;
use App\Repositories\SlotRepository;
use App\Services\SlotService;
use Illuminate\Routing\UrlGenerator;
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
        $this->app->bind(SlotRepositoryInterface::class, SlotRepository::class);
        $this->app->bind(HoldRepositoryInterface::class, HoldRepository::class);
        $this->app->bind(SlotServiceInterface::class, SlotService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(UrlGenerator $url)
    {

        // Использовать для публикации в ngrok
        // ngrok http 127.0.0.1:8000
//        $url->forceScheme('https');
    }
}
