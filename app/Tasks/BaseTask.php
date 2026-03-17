<?php

namespace App\Tasks;

use App\Models\AgentConfig;
use App\Models\Execution;
use App\Services\ExecutionResult;
use Illuminate\Support\Facades\Log;

abstract class BaseTask
{
    protected string $slug;
    protected string $name;
    protected ?string $cronExpression = null;
    protected int $timeout = 60;

    /**
     * Lógica principal da task — código PHP puro, sem LLM.
     */
    abstract public function handle(): ExecutionResult;

    /**
     * Executa a task com logging na tabela executions.
     */
    public function run(): ExecutionResult
    {
        $execution = null;

        if (config('laraclaw.log_executions')) {
            $execution = Execution::create([
                'agent_slug' => $this->slug,
                'status' => 'running',
                'prompt' => '[task] ' . static::class,
                'started_at' => now(),
            ]);
        }

        try {
            $result = $this->handle();
        } catch (\Throwable $e) {
            $result = ExecutionResult::error("Exception: {$e->getMessage()}");
        }

        if ($execution) {
            $execution->update([
                'status' => $result->success ? 'success' : 'error',
                'finished_at' => now(),
                'duration_ms' => $result->durationMs,
                'cost_usd' => 0,
                'output_result' => $result->content,
                'error_log' => $result->error,
            ]);
        }

        $this->updateLastRun();

        if ($result->success) {
            $this->onSuccess($result);
        } else {
            $this->onError($result);
        }

        return $result;
    }

    public function onSuccess(ExecutionResult $result): void
    {
        //
    }

    public function onError(ExecutionResult $result): void
    {
        //
    }

    public function getCronExpression(): ?string
    {
        $config = $this->getConfig();

        if ($config && $config->cron_expression) {
            return $config->cron_expression;
        }

        return $this->cronExpression;
    }

    public function isActive(): bool
    {
        $config = $this->getConfig();

        if ($config) {
            return $config->is_active;
        }

        return true;
    }

    public function isScheduled(): bool
    {
        return $this->getCronExpression() !== null;
    }

    public function getConfig(): ?AgentConfig
    {
        try {
            return AgentConfig::where('slug', $this->slug)->first();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function updateLastRun(): void
    {
        try {
            AgentConfig::updateOrCreate(
                ['slug' => $this->slug],
                ['last_run_at' => now()]
            );
        } catch (\Throwable) {
            //
        }
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getType(): string
    {
        return 'task';
    }
}
