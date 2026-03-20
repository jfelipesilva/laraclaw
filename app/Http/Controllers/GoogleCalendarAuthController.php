<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;

class GoogleCalendarAuthController extends Controller
{
    public function redirect()
    {
        $service = app(GoogleCalendarService::class);
        $client = $service->getClient();
        $client->setRedirectUri(url('/'));

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
        $client->setRedirectUri(url('/'));

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
}
