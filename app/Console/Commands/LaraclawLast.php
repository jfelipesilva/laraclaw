<?php

namespace App\Console\Commands;

use App\Models\Execution;
use App\Services\RunnableRegistry;
use Illuminate\Console\Command;

class LaraclawLast extends Command
{
    protected $signature = 'laraclaw:last {slug : Slug do agente ou task}';
    protected $description = 'Mostra a última execução de um agente ou task';

    public function handle(): int
    {
        $slug = $this->argument('slug');
        $runnable = RunnableRegistry::find($slug);

        if (!$runnable) {
            $this->error("Runnable [{$slug}] não encontrado.");
            return self::FAILURE;
        }

        $execution = Execution::where('agent_slug', $slug)
            ->latest('started_at')
            ->first();

        if (!$execution) {
            $this->warn("Nenhuma execução encontrada para [{$slug}].");
            return self::SUCCESS;
        }

        $cost = $execution->cost_usd ? '$' . number_format($execution->cost_usd, 4) : '-';
        $type = strtoupper($runnable->getType());

        $this->info("[{$type}] {$runnable->getName()}");
        $this->line("Status: {$execution->status}");
        $this->line("Executed: {$execution->started_at}");
        $this->line("Duration: " . ($execution->duration_ms ?? '-') . "ms | Cost: {$cost}");

        if ($execution->status === 'success') {
            $this->line('Output: ' . ($execution->output_result ?? '-'));
        } else {
            $this->error('Error: ' . ($execution->error_log ?? '-'));
        }

        return self::SUCCESS;
    }
}
