<?php

namespace App\Services;

use Illuminate\Support\Collection;

class RunnableRegistry
{
    /**
     * Retorna todos os runnables (agents + tasks) combinados.
     */
    public static function all(): Collection
    {
        return AgentRegistry::all()->merge(TaskRegistry::all());
    }

    /**
     * Busca um runnable (agent ou task) pelo slug.
     */
    public static function find(string $slug): mixed
    {
        return AgentRegistry::find($slug) ?? TaskRegistry::find($slug);
    }

    /**
     * Retorna todos os slugs disponíveis.
     */
    public static function slugs(): array
    {
        return array_merge(AgentRegistry::slugs(), TaskRegistry::slugs());
    }

    /**
     * Retorna apenas os runnables que têm cron e estão ativos.
     */
    public static function scheduled(): Collection
    {
        return static::all()->filter(fn ($r) => $r->isScheduled() && $r->isActive());
    }
}
