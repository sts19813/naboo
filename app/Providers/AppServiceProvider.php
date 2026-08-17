<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\Services\PendingNotificationService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        User::observe(UserObserver::class);

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
