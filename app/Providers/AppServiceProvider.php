<?php

namespace App\Providers;

use App\Services\PendingNotificationService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PendingNotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return URL::route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        });

        Paginator::useBootstrapFive();

        View::composer(['partials.sidebar', 'partials.topbar'], function ($view): void {
            $view->with(
                'pendingNotifications',
                app(PendingNotificationService::class)->forUser(auth()->user()),
            );
        });
    }
}
