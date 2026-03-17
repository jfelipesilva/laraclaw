<?php

namespace App\Tasks;

use App\Models\ClickupTask;
use App\Services\ClickUpService;
use App\Services\ExecutionResult;
use Illuminate\Support\Facades\Log;

class ClickupSyncTask extends BaseTask
{
    protected string $slug = 'clickup-sync';
    protected string $name = 'Sync Tasks ClickUp';
    protected ?string $cronExpression = '*/5 * * * *';
    protected int $timeout = 30;

    public function handle(): ExecutionResult
    {
        $service = app(ClickUpService::class);
        $startTime = microtime(true);

        $tasks = $service->getTasksFromList(includeClosed: true);

        if ($tasks === null || (is_array($tasks) && empty($tasks) && $tasks !== [])) {
            // Se retornou null, houve erro na API
            if ($tasks === null) {
                return ExecutionResult::error('Falha ao buscar tasks do ClickUp');
            }
        }

        $synced = 0;
        $devIds = collect(config('laraclaw.clickup.devs'))->pluck('clickup_id')->all();

        foreach ($tasks as $task) {
            $assigneeId = $task['assignees'][0]['id'] ?? null;

            // Sync todas as tasks, nao apenas dos devs monitorados
            $priority = $task['priority']['priority'] ?? null;
            $dueDate = isset($task['due_date']) && $task['due_date']
                ? date('Y-m-d H:i:s', $task['due_date'] / 1000)
                : null;
            $dateCreated = isset($task['date_created'])
                ? date('Y-m-d H:i:s', $task['date_created'] / 1000)
                : null;
            $dateDone = isset($task['date_done']) && $task['date_done']
                ? date('Y-m-d H:i:s', $task['date_done'] / 1000)
                : null;

            $tags = array_map(fn ($t) => $t['name'], $task['tags'] ?? []);

            ClickupTask::updateOrCreate(
                ['clickup_id' => $task['id']],
                [
                    'name' => $task['name'],
                    'status' => $task['status']['status'] ?? 'unknown',
                    'status_color' => $task['status']['color'] ?? null,
                    'assignee_id' => $assigneeId,
                    'assignee_name' => $task['assignees'][0]['username'] ?? null,
                    'priority' => $priority,
                    'project' => $task['list']['name'] ?? null,
                    'due_date' => $dueDate,
                    'date_created' => $dateCreated,
                    'date_done' => $dateDone,
                    'url' => $task['url'] ?? null,
                    'tags' => $tags,
                ]
            );

            $synced++;
        }

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        return ExecutionResult::success(
            content: json_encode([
                'synced' => $synced,
                'dev_ids_monitored' => count($devIds),
                'total_tasks' => count($tasks),
            ]),
            durationMs: $durationMs,
        );
    }

    public function onSuccess(ExecutionResult $result): void
    {
        $data = $result->json();
        Log::channel('laraclaw')->info("ClickUp sync: {$data['synced']} tasks synced", $data);
    }

    public function onError(ExecutionResult $result): void
    {
        Log::channel('laraclaw')->error("ClickUp sync failed: {$result->error}");
    }
}
