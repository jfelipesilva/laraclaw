<?php

namespace App\Services;

use App\Models\Execution;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class ClaudeRunner
{
    /**
     * Executa um prompt no Claude CLI e retorna o resultado.
     */
    public function execute(string $prompt, array $options = []): ExecutionResult
    {
        $command = $this->buildCommand($prompt, $options);
        $timeout = $options['timeout'] ?? config('laraclaw.default_timeout');
        $directory = $options['directory'] ?? base_path();
        $agentSlug = $options['agent_slug'] ?? null;

        $execution = null;

        if (config('laraclaw.log_executions')) {
            $execution = Execution::create([
                'agent_slug' => $agentSlug,
                'status' => 'running',
                'prompt' => $prompt,
                'system_prompt' => $options['system_prompt'] ?? null,
                'directory' => $directory,
                'started_at' => now(),
            ]);
        }

        try {
            $process = Process::timeout($timeout)
                ->path($directory)
                ->run($command);
        } catch (ProcessTimedOutException $e) {
            $result = ExecutionResult::error("Timeout após {$timeout}s");
            $this->updateExecution($execution, $result);
            return $result;
        }

        if ($process->failed()) {
            $stderr = $process->errorOutput();
            $result = ExecutionResult::error("Processo falhou: {$stderr}");
            $this->updateExecution($execution, $result);
            return $result;
        }

        $output = $process->output();
        $decoded = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $result = ExecutionResult::error("JSON inválido no retorno do CLI: " . substr($output, 0, 500));
            $this->updateExecution($execution, $result);
            return $result;
        }

        if (($decoded['subtype'] ?? null) !== 'success') {
            $errorMsg = $decoded['result'] ?? $decoded['error'] ?? 'Erro desconhecido';
            $result = ExecutionResult::error("Claude retornou erro: {$errorMsg}");
            $this->updateExecution($execution, $result);
            return $result;
        }

        $result = ExecutionResult::success(
            content: $decoded['result'] ?? '',
            costUsd: $decoded['cost_usd'] ?? null,
            durationMs: $decoded['duration_ms'] ?? null,
            numTurns: $decoded['num_turns'] ?? null,
            sessionId: $decoded['session_id'] ?? null,
        );

        $this->updateExecution($execution, $result);

        return $result;
    }

    /**
     * Monta o comando completo para o Claude CLI.
     */
    protected function buildCommand(string $prompt, array $options): array
    {
        $binary = config('laraclaw.claude_binary');
        $cmd = [$binary, '-p', $prompt, '--output-format', 'json'];

        if ($systemPrompt = $options['system_prompt'] ?? null) {
            $cmd = [...$cmd, '--system-prompt', $systemPrompt];
        }

        if ($appendSystemPrompt = $options['append_system_prompt'] ?? null) {
            $cmd = [...$cmd, '--append-system-prompt', $appendSystemPrompt];
        }

        $maxTurns = $options['max_turns'] ?? config('laraclaw.default_max_turns');
        $cmd = [...$cmd, '--max-turns', (string) $maxTurns];

        if ($allowedTools = $options['allowed_tools'] ?? null) {
            $cmd = [...$cmd, '--allowedTools', implode(',', $allowedTools)];
        }

        if ($disallowedTools = $options['disallowed_tools'] ?? null) {
            $cmd = [...$cmd, '--disallowedTools', implode(',', $disallowedTools)];
        }

        return $cmd;
    }

    /**
     * Atualiza o registro de execução no banco.
     */
    protected function updateExecution(?Execution $execution, ExecutionResult $result): void
    {
        if (!$execution) {
            return;
        }

        $execution->update([
            'status' => $result->success ? 'success' : 'error',
            'finished_at' => now(),
            'duration_ms' => $result->durationMs,
            'cost_usd' => $result->costUsd,
            'num_turns' => $result->numTurns,
            'session_id' => $result->sessionId,
            'output_result' => $result->content,
            'error_log' => $result->error,
        ]);
    }
}
