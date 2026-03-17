<?php

namespace App\Services;

use App\Agents\BaseAgent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class AgentRegistry
{
    protected static ?Collection $agents = null;

    /**
     * Retorna todos os agentes registrados.
     */
    public static function all(): Collection
    {
        if (static::$agents === null) {
            static::$agents = static::discover();
        }

        return static::$agents;
    }

    /**
     * Busca um agente pelo slug.
     */
    public static function find(string $slug): ?BaseAgent
    {
        return static::all()->first(fn (BaseAgent $agent) => $agent->getSlug() === $slug);
    }

    /**
     * Retorna todos os slugs disponíveis.
     */
    public static function slugs(): array
    {
        return static::all()->map(fn (BaseAgent $agent) => $agent->getSlug())->values()->all();
    }

    /**
     * Escaneia app/Agents/ e instancia todas as classes que estendem BaseAgent.
     */
    protected static function discover(): Collection
    {
        $agentsPath = app_path('Agents');
        $agents = collect();

        if (!File::isDirectory($agentsPath)) {
            return $agents;
        }

        $files = File::files($agentsPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = 'App\\Agents\\' . $file->getFilenameWithoutExtension();

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if ($reflection->isAbstract() || !$reflection->isSubclassOf(BaseAgent::class)) {
                continue;
            }

            $agents->push(new $className());
        }

        return $agents;
    }

    /**
     * Limpa o cache para forçar re-discovery.
     */
    public static function flush(): void
    {
        static::$agents = null;
    }
}
