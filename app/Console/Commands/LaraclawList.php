<?php

namespace App\Console\Commands;

use App\Services\RunnableRegistry;
use Illuminate\Console\Command;

class LaraclawList extends Command
{
    protected $signature = 'laraclaw:list';
    protected $description = 'Lista todos os agentes e tasks registrados';

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
            $r->getTimeout() . 's',
        ]);

        $this->table(
            ['Slug', 'Name', 'Type', 'Cron', 'Active', 'Timeout'],
            $rows
        );

        return self::SUCCESS;
    }
}
