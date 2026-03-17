<?php

namespace App\Services;

class ExecutionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $content = null,
        public readonly ?float $costUsd = null,
        public readonly ?int $durationMs = null,
        public readonly ?int $numTurns = null,
        public readonly ?string $sessionId = null,
        public readonly ?string $error = null,
    ) {}

    public static function success(
        string $content,
        ?float $costUsd = null,
        ?int $durationMs = null,
        ?int $numTurns = null,
        ?string $sessionId = null,
    ): self {
        return new self(
            success: true,
            content: $content,
            costUsd: $costUsd,
            durationMs: $durationMs,
            numTurns: $numTurns,
            sessionId: $sessionId,
        );
    }

    public static function error(string $error): self
    {
        return new self(
            success: false,
            error: $error,
        );
    }

    /**
     * Parseia o content como JSON.
     */
    public function json(): ?array
    {
        if (!$this->content) {
            return null;
        }

        $decoded = json_decode($this->content, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}
