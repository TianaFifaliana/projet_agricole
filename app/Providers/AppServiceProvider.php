<?php

namespace App\Providers;

use App\Listeners\InvalidationCacheSubscriber;
use App\Listeners\JournalisationSubscriber;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::subscribe(InvalidationCacheSubscriber::class);
        Event::subscribe(JournalisationSubscriber::class);
    }
}
