<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClickUpService
{
    protected string $baseUrl = 'https://api.clickup.com/api/v2';
    protected string $token;
    protected string $teamId;
    protected string $listId;

    public function __construct()
    {
        $this->token = config('laraclaw.clickup.token');
        $this->teamId = config('laraclaw.clickup.team_id');
        $this->listId = config('laraclaw.clickup.list_id');
    }

    /**
     * Busca todas as tasks da lista PRODUCAO (paginado).
     */
    public function getTasksFromList(array $statuses = [], bool $includeClosed = false): array
    {
        $allTasks = [];
        $page = 0;

        do {
            $query = [
                'page' => $page,
                'include_closed' => $includeClosed ? 'true' : 'false',
                'subtasks' => 'true',
            ];

            foreach ($statuses as $i => $status) {
                $query["statuses[{$i}]"] = $status;
            }

            $response = $this->request("/list/{$this->listId}/task", $query);

            if (!$response) {
                break;
            }

            $tasks = $response['tasks'] ?? [];
            $allTasks = array_merge($allTasks, $tasks);
            $page++;
        } while (count($tasks) === 100);

        return $allTasks;
    }

    /**
     * Busca tasks filtradas por assignee e status.
     */
    public function getTasksByAssignee(int $assigneeId, array $statuses = []): array
    {
        $query = [
            'assignees[]' => $assigneeId,
            'include_closed' => 'false',
            'subtasks' => 'true',
        ];

        foreach ($statuses as $i => $status) {
            $query["statuses[{$i}]"] = $status;
        }

        $response = $this->request("/list/{$this->listId}/task", $query);

        return $response['tasks'] ?? [];
    }

    /**
     * Busca tasks concluidas no periodo.
     */
    public function getCompletedTasks(string $dateFrom, string $dateTo): array
    {
        $query = [
            'include_closed' => 'true',
            'date_done_gt' => strtotime($dateFrom) * 1000,
            'date_done_lt' => strtotime($dateTo) * 1000,
            'list_ids[]' => $this->listId,
        ];

        $response = $this->request("/team/{$this->teamId}/task", $query);

        return $response['tasks'] ?? [];
    }

    /**
     * Request generico ao ClickUp API.
     */
    protected function request(string $endpoint, array $query = []): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->timeout(15)->get("{$this->baseUrl}{$endpoint}", $query);

            if ($response->failed()) {
                Log::channel('laraclaw')->error("ClickUp API error: HTTP {$response->status()} on {$endpoint}", [
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::channel('laraclaw')->error("ClickUp API exception: {$e->getMessage()}");
            return null;
        }
    }
}
