<?php

namespace App\Http\Controllers;

use App\Models\AgentConfig;
use App\Services\GoogleCalendarService;
use Google\Service\Calendar;

class GoogleCalendarAuthController extends Controller
{
    protected function getRedirectUri(): string
    {
        $credentialsPath = config('laraclaw.google_calendar.credentials_path');
        $credentials = json_decode(file_get_contents($credentialsPath), true);
        $type = isset($credentials['web']) ? 'web' : 'installed';

        return $credentials[$type]['redirect_uris'][0] ?? url('/');
    }

    public function redirect()
    {
        $service = app(GoogleCalendarService::class);
        $client = $service->getClient();
        $client->setRedirectUri($this->getRedirectUri());

        $authUrl = $client->createAuthUrl();

        return redirect($authUrl);
    }

    public function callback()
    {
        $code = request('code');

        if (!$code) {
            return response('Codigo de autorizacao nao recebido.', 400);
        }

        $service = app(GoogleCalendarService::class);
        $client = $service->getClient();
        $client->setRedirectUri($this->getRedirectUri());

        try {
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                return response("Erro OAuth: {$token['error_description']}", 400);
            }

            $service->saveToken($token);

            return '<pre style="color:lime;background:#000;padding:20px;font-family:monospace">'
                . '[LARACLAW] Google Calendar autorizado com sucesso!'
                . "\n\nToken salvo. O sync vai iniciar automaticamente."
                . "\n\n<a href=\"/\" style=\"color:cyan\">Voltar ao Dashboard</a></pre>";
        } catch (\Throwable $e) {
            return response("Erro: {$e->getMessage()}", 500);
        }
    }

    public function listCalendars()
    {
        $service = app(GoogleCalendarService::class);

        if (!$service->isAuthenticated()) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $cal = new Calendar($service->getClient());
        $items = $cal->calendarList->listCalendarList()->getItems();

        $config = AgentConfig::where('slug', 'google-calendar-sync')->first();
        $selected = $config?->metadata['selected_calendars'] ?? [];

        $calendars = array_map(fn ($c) => [
            'id' => $c->getId(),
            'name' => $c->getSummary(),
            'color' => $c->getBackgroundColor(),
            'selected' => empty($selected) || in_array($c->getId(), $selected),
        ], $items);

        return response()->json($calendars);
    }

    public function saveCalendars()
    {
        $ids = request('calendar_ids', []);

        $config = AgentConfig::updateOrCreate(
            ['slug' => 'google-calendar-sync'],
            ['metadata' => ['selected_calendars' => $ids]]
        );

        // Trigger sync imediato
        $runnable = \App\Services\RunnableRegistry::find('google-calendar-sync');
        if ($runnable) {
            $runnable->run();
        }

        return response()->json(['ok' => true, 'selected' => count($ids)]);
    }
}
