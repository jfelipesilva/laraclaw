<?php

namespace App\Console\Commands;

use App\Services\RunnableRegistry;
use Illuminate\Console\Command;

class LaraclawScheduleList extends Command
{
    protected $signature = 'laraclaw:schedule:list';
    protected $description = 'Lista agendamentos dos agentes e tasks';

    public function handle(): int
    {
        $runnables = RunnableRegistry::all();

        if ($runnables->isEmpty()) {
            $this->warn('Nenhum agente ou task encontrado.');
            return self::SUCCESS;
        }

        $rows = $runnables->map(fn ($r) => [
            $r->getSlug(),
            $r->getName(),
            strtoupper($r->getType()),
            $r->getCronExpression() ?? '-',
            $r->isScheduled() ? ($r->isActive() ? '✓' : '✗') : '-',
            $r->getConfig()?->last_run_at?->format('Y-m-d H:i:s') ?? 'never',
        ]);

        $this->table(
            ['Slug', 'Name', 'Type', 'Cron', 'Active', 'Last Run'],
            $rows
        );

        return self::SUCCESS;
    }
}
