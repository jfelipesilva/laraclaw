<?php

use App\Services\RunnableRegistry;
use Illuminate\Support\Facades\Schedule;

// Registra todas as tasks/agents com cron no scheduler do Laravel
foreach (RunnableRegistry::scheduled() as $runnable) {
    Schedule::call(fn () => $runnable->run())
        ->cron($runnable->getCronExpression())
        ->name($runnable->getSlug())
        ->withoutOverlapping();
}
