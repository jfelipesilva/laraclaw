<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    protected Client $client;
    protected string $credentialsPath;
    protected string $tokenPath;
    protected string $calendarId;

    public function __construct()
    {
        $this->credentialsPath = config('laraclaw.google_calendar.credentials_path');
        $this->tokenPath = config('laraclaw.google_calendar.token_path');
        $this->calendarId = config('laraclaw.google_calendar.calendar_id');

        $this->client = new Client();
        $this->client->setApplicationName('LaraClaw');
        $this->client->setScopes(Calendar::CALENDAR_READONLY);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        if (file_exists($this->credentialsPath)) {
            $this->client->setAuthConfig($this->credentialsPath);
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function isAuthenticated(): bool
    {
        if (!file_exists($this->tokenPath)) {
            return false;
        }

        $token = json_decode(file_get_contents($this->tokenPath), true);
        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            return $this->refreshToken();
        }

        return true;
    }

    protected function refreshToken(): bool
    {
        $refreshToken = $this->client->getRefreshToken();

        if (!$refreshToken) {
            return false;
        }

        try {
            $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);

            if (isset($newToken['error'])) {
                Log::channel('laraclaw')->error("Google Calendar token refresh failed: {$newToken['error']}");
                return false;
            }

            $this->saveToken($newToken);
            return true;
        } catch (\Throwable $e) {
            Log::channel('laraclaw')->error("Google Calendar token refresh exception: {$e->getMessage()}");
            return false;
        }
    }

    public function saveToken(array $token): void
    {
        $dir = dirname($this->tokenPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        file_put_contents($this->tokenPath, json_encode($token));
    }

    public function getUpcomingEvents(int $days = 7): ?array
    {
        if (!$this->isAuthenticated()) {
            Log::channel('laraclaw')->error('Google Calendar: not authenticated');
            return null;
        }

        try {
            $service = new Calendar($this->client);

            $now = now();
            $params = [
                'calendarId' => $this->calendarId,
                'timeMin' => $now->toRfc3339String(),
                'timeMax' => $now->copy()->addDays($days)->endOfDay()->toRfc3339String(),
                'singleEvents' => true,
                'orderBy' => 'startTime',
                'maxResults' => 100,
            ];

            $results = $service->events->listEvents($this->calendarId, $params);

            return array_map(function ($event) {
                $start = $event->getStart();
                $end = $event->getEnd();
                $allDay = !empty($start->getDate());

                return [
                    'id' => $event->getId(),
                    'title' => $event->getSummary() ?? '(sem titulo)',
                    'description' => $event->getDescription(),
                    'location' => $event->getLocation(),
                    'start_at' => $allDay
                        ? $start->getDate() . ' 00:00:00'
                        : date('Y-m-d H:i:s', strtotime($start->getDateTime())),
                    'end_at' => $allDay
                        ? date('Y-m-d H:i:s', strtotime($end->getDate() . ' -1 day'))
                        : date('Y-m-d H:i:s', strtotime($end->getDateTime())),
                    'all_day' => $allDay,
                    'status' => $event->getStatus() ?? 'confirmed',
                    'html_link' => $event->getHtmlLink(),
                ];
            }, $results->getItems());
        } catch (\Throwable $e) {
            Log::channel('laraclaw')->error("Google Calendar API error: {$e->getMessage()}");
            return null;
        }
    }
}
