<?php

namespace App\Tasks;

use App\Services\ExecutionResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VeicoPlatesCreditLeftTask extends BaseTask
{
    use Concerns\SyncOnDemand;

    protected string $slug = 'veicoplatescreditsleft';
    protected string $name = 'Créditos API Placas Veico';
    protected int $timeout = 30;

    protected int $alertThreshold = 100;

    public function handle(): ExecutionResult
    {
        $token = config('laraclaw.veico_plates_token');

        if (!$token) {
            return ExecutionResult::error('Token da API de placas não configurado (LARACLAW_VEICO_PLATES_TOKEN)');
        }

        $startTime = microtime(true);

        $response = Http::timeout($this->timeout)
            ->get("https://wdapi2.com.br/saldo/{$token}");

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        if ($response->failed()) {
            return ExecutionResult::error(
                "API retornou HTTP {$response->status()}: " . $response->body()
            );
        }

        $data = $response->json();

        if (!isset($data['qtdConsultas'])) {
            return ExecutionResult::error('Resposta inesperada da API: ' . $response->body());
        }

        $credits = (int) $data['qtdConsultas'];
        $alert = $credits < $this->alertThreshold;

        $result = [
            'credits' => $credits,
            'alert' => $alert,
            'threshold' => $this->alertThreshold,
            'checked_at' => now()->toIso8601String(),
        ];

        return ExecutionResult::success(
            content: json_encode($result),
            durationMs: $durationMs,
        );
    }

    public function onSuccess(ExecutionResult $result): void
    {
        $data = $result->json();

        Log::channel('laraclaw')->info("Veico plates credits: {$data['credits']}", $data);

        if ($data['alert']) {
            Log::channel('laraclaw')->warning(
                "ALERTA: Créditos abaixo do threshold! {$data['credits']}/{$data['threshold']}"
            );
        }
    }

    public function onError(ExecutionResult $result): void
    {
        Log::channel('laraclaw')->error("Falha ao verificar créditos: {$result->error}");
    }
}
