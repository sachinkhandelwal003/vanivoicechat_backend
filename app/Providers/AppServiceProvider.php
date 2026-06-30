<?php

namespace App\Providers;

use App\Http\Kernel;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use App\Models\SupportMessage;
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
    public function boot(Kernel $kernel): void
    {
        Paginator::useBootstrapFive();
        View::composer('*', function ($view) {

            $totalUnread = SupportMessage::where('sender_type', 'user')
                ->where('is_read', false)
                ->count();

            $view->with('totalUnread', $totalUnread);
        });
    }
}
