<?php

namespace App\Agents;

use App\Services\ExecutionResult;

class PingAgent extends BaseAgent
{
    protected string $slug = 'ping';
    protected string $name = 'Ping Test';
    protected int $maxTurns = 1;
    protected int $timeout = 30;

    public function prompt(): string
    {
        return 'Responda apenas com o JSON: {"status": "pong", "timestamp": "<unix timestamp atual>"}';
    }

    public function validateOutput(ExecutionResult $result): bool
    {
        $json = $result->json();
        return $json && isset($json['status']) && $json['status'] === 'pong';
    }
}
