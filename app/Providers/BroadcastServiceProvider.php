<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    // public function boot(): void
    // {
    //     // Route::middleware([
    //     //     'api',
    //     //     'auth:sanctum'
    //     // ])->group(function () {

    //     //     Broadcast::routes();
    //     // });

    //     require base_path('routes/channels.php');
    // }

    public function boot(): void
    {
        // 1. Route yahan register karein:
        Broadcast::routes(['middleware' => ['auth:sanctum']]);

        // 2. Uske baad channels file require karein:
        require base_path('routes/channels.php');
    }
}
