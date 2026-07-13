<?php

use App\Console\Commands\GenerateRecurringExpensesCommand;
use App\Console\Commands\SendExpenseNotificationsCommand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

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
    ])
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('expenses:generate-recurring')->dailyAt('07:45')->withoutOverlapping();
        $schedule->command('expenses:notify')->dailyAt('08:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'system.access' => \App\Http\Middleware\EnsureUserHasSystemAccess::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
