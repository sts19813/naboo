<?php

use App\Console\Commands\GenerateRecurringExpensesCommand;
use App\Console\Commands\IssueAirbnbIntegrationTokenCommand;
use App\Console\Commands\ReportBillingUsage;
use App\Console\Commands\SendExpenseNotificationsCommand;
use App\Console\Commands\SyncCentralUsers;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsurePropertyIsVisible;
use App\Http\Middleware\EnsureStorageAccess;
use App\Http\Middleware\EnsureUserHasSystemAccess;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use App\Http\Middleware\EnsureAirbnbIntegrationEnabled;
use App\Http\Middleware\EnsureActiveIntegrationClient;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        GenerateRecurringExpensesCommand::class,
        SendExpenseNotificationsCommand::class,
        ReportBillingUsage::class,
        SyncCentralUsers::class,
        IssueAirbnbIntegrationTokenCommand::class,
    ])
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('expenses:generate-recurring')->dailyAt('07:45')->withoutOverlapping();
        $schedule->command('expenses:notify')->dailyAt('08:00');
        $schedule->command('billing:report-usage')->everyFifteenMinutes()->withoutOverlapping();
        $schedule->command('sso:sync-users')->everyFiveMinutes()->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => EnsureEmailIsVerified::class,
            'system.access' => EnsureUserHasSystemAccess::class,
            'storage.access' => EnsureStorageAccess::class,
            'property.visible' => EnsurePropertyIsVisible::class,
            'abilities' => CheckAbilities::class,
            'airbnb.enabled' => EnsureAirbnbIntegrationEnabled::class,
            'integration.client' => EnsureActiveIntegrationClient::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
