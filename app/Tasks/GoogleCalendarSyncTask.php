<?php

namespace App\Tasks;

use App\Models\CalendarEvent;
use App\Services\ExecutionResult;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Log;

class GoogleCalendarSyncTask extends BaseTask
{
    use Concerns\SyncOnDemand;

    protected string $slug = 'google-calendar-sync';
    protected string $name = 'Sync Google Calendar';
    protected int $timeout = 30;

    public function handle(): ExecutionResult
    {
        $service = app(GoogleCalendarService::class);
        $startTime = microtime(true);
        $days = config('laraclaw.google_calendar.sync_days_ahead', 7);

        $events = $service->getUpcomingEvents($days);

        if ($events === null) {
            return ExecutionResult::error('Falha ao buscar eventos do Google Calendar');
        }

        $synced = 0;
        $syncedIds = [];

        foreach ($events as $event) {
            CalendarEvent::updateOrCreate(
                ['google_event_id' => $event['id']],
                [
                    'title' => $event['title'],
                    'description' => $event['description'],
                    'location' => $event['location'],
                    'start_at' => $event['start_at'],
                    'end_at' => $event['end_at'],
                    'all_day' => $event['all_day'],
                    'status' => $event['status'],
                    'calendar_name' => $event['calendar_name'] ?? null,
                    'color' => $event['color'] ?? null,
                    'html_link' => $event['html_link'],
                ]
            );

            $syncedIds[] = $event['id'];
            $synced++;
        }

        // Remove events that were deleted from Google Calendar (within sync window)
        $now = now();
        CalendarEvent::where('start_at', '>=', $now)
            ->where('start_at', '<=', $now->copy()->addDays($days)->endOfDay())
            ->whereNotIn('google_event_id', $syncedIds)
            ->delete();

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        return ExecutionResult::success(
            content: json_encode([
                'synced' => $synced,
                'days_ahead' => $days,
            ]),
            durationMs: $durationMs,
        );
    }

    public function onSuccess(ExecutionResult $result): void
    {
        $data = $result->json();
        Log::channel('laraclaw')->info("Google Calendar sync: {$data['synced']} events synced", $data);
    }

    public function onError(ExecutionResult $result): void
    {
        Log::channel('laraclaw')->error("Google Calendar sync failed: {$result->error}");
    }
}
