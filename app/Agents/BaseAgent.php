<?php

namespace App\Agents;

use App\Models\AgentConfig;
use App\Services\ClaudeRunner;
use App\Services\ExecutionResult;

abstract class BaseAgent
{
    protected string $slug;
    protected string $name;
    protected ?string $systemPrompt = null;
    protected ?string $directory = null;
    protected int $maxTurns = 10;
    protected int $timeout = 120;
    protected array $allowedTools = [];
    protected array $disallowedTools = [];
    protected ?string $cronExpression = null;

    abstract public function prompt(): string;

    public function validateOutput(ExecutionResult $result): bool
    {
        return true;
    }

    public function onSuccess(ExecutionResult $result): void
    {
        //
    }

    public function onError(ExecutionResult $result): void
    {
        //
    }

    /**
     * Executa o agente: prompt → Claude CLI → validação → callbacks.
     */
    public function run(): ExecutionResult
    {
        $runner = app(ClaudeRunner::class);

        $options = [
            'agent_slug' => $this->slug,
            'timeout' => $this->timeout,
            'max_turns' => $this->maxTurns,
        ];

        if ($this->systemPrompt) {
            $options['system_prompt'] = $this->systemPrompt;
        }

        if ($this->directory) {
            $options['directory'] = $this->directory;
        }

        if ($this->allowedTools) {
            $options['allowed_tools'] = $this->allowedTools;
        }

        if ($this->disallowedTools) {
            $options['disallowed_tools'] = $this->disallowedTools;
        }

        $result = $runner->execute($this->prompt(), $options);

        if ($result->success && $this->validateOutput($result)) {
            $this->onSuccess($result);
        } else {
            if ($result->success) {
                $result = ExecutionResult::error(
                    "Validação de output falhou para o agente [{$this->slug}]"
                );
            }
            $this->onError($result);
        }

        // Atualiza last_run_at no agent_configs
        $this->updateLastRun();

        return $result;
    }

    /**
     * Retorna o cron expression efetivo (banco sobrescreve classe).
     */
    public function getCronExpression(): ?string
    {
        $config = $this->getConfig();

        if ($config && $config->cron_expression) {
            return $config->cron_expression;
        }

        return $this->cronExpression;
    }

    /**
     * Verifica se o agente está ativo (banco sobrescreve classe).
     */
    public function isActive(): bool
    {
        $config = $this->getConfig();

        if ($config) {
            return $config->is_active;
        }

        return true;
    }

    /**
     * Verifica se o agente tem agendamento (cron definido).
     */
    public function isScheduled(): bool
    {
        return $this->getCronExpression() !== null;
    }

    /**
     * Retorna o AgentConfig do banco, ou null se não existir.
     */
    public function getConfig(): ?AgentConfig
    {
        try {
            return AgentConfig::where('slug', $this->slug)->first();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Atualiza last_run_at no agent_configs.
     */
    protected function updateLastRun(): void
    {
        AgentConfig::updateOrCreate(
            ['slug' => $this->slug],
            ['last_run_at' => now()]
        );
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

    public function getMaxTurns(): int
    {
        return $this->maxTurns;
    }

    public function getDirectory(): ?string
    {
        return $this->directory;
    }

    public function getType(): string
    {
        return 'agent';
    }
}
