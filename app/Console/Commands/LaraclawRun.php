<?php

namespace App\Console\Commands;

use App\Services\RunnableRegistry;
use Illuminate\Console\Command;

class LaraclawRun extends Command
{
    protected $signature = 'laraclaw:run {slug : Slug do agente ou task a executar}';
    protected $description = 'Executa um agente ou task manualmente';

    public function handle(): int
    {
        $slug = $this->argument('slug');
        $runnable = RunnableRegistry::find($slug);

        if (!$runnable) {
            $this->error("Runnable [{$slug}] não encontrado.");
            $this->line('Disponíveis: ' . implode(', ', RunnableRegistry::slugs()));
            return self::FAILURE;
        }

        $type = strtoupper($runnable->getType());
        $this->info("Running [{$type}] {$runnable->getName()}...");

        $result = $runnable->run();

        if ($result->success) {
            $cost = $result->costUsd ? '$' . number_format($result->costUsd, 4) : '-';
            $duration = $result->durationMs ? round($result->durationMs / 1000, 1) . 's' : '-';
            $turns = $result->numTurns ?? '-';

            $this->info("✓ Success ({$duration} | {$cost} | {$turns} turns)");
            $this->line('Output: ' . ($result->content ?? '-'));
        } else {
            $this->error("✗ Error: {$result->error}");
        }

        return $result->success ? self::SUCCESS : self::FAILURE;
    }
}
