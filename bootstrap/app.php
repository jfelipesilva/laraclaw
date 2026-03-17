<?php

use App\Services\RunnableRegistry;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        try {
            $runnables = RunnableRegistry::all();
        } catch (\Throwable) {
            return;
        }

        foreach ($runnables as $runnable) {
            $cron = $runnable->getCronExpression();

            if (!$cron || !$runnable->isActive()) {
                continue;
            }

            $schedule->command("laraclaw:run {$runnable->getSlug()}")
                ->cron($cron)
                ->withoutOverlapping()
                ->onOneServer()
                ->runInBackground();
        }
    })
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
