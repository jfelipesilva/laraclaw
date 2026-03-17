<?php

namespace App\Console\Commands;

use App\Models\AgentConfig;
use App\Services\RunnableRegistry;
use Illuminate\Console\Command;

class LaraclawScheduleToggle extends Command
{
    protected $signature = 'laraclaw:schedule:toggle {slug : Slug do agente ou task}';
    protected $description = 'Ativa/desativa o agendamento de um agente ou task';

    public function handle(): int
    {
        $slug = $this->argument('slug');
        $runnable = RunnableRegistry::find($slug);

        if (!$runnable) {
            $this->error("[{$slug}] não encontrado.");
            return self::FAILURE;
        }

        if (!$runnable->isScheduled()) {
            $this->warn("[{$slug}] não possui agendamento (sem cron_expression).");
            return self::FAILURE;
        }

        $config = AgentConfig::firstOrCreate(
            ['slug' => $slug],
            ['is_active' => true]
        );

        $config->update(['is_active' => !$config->is_active]);

        $status = $config->is_active ? 'ativado' : 'desativado';
        $this->info("[{$slug}] {$status}.");

        return self::SUCCESS;
    }
}
