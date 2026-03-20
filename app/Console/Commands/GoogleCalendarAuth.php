<?php

namespace App\Console\Commands;

use App\Services\GoogleCalendarService;
use Illuminate\Console\Command;

class GoogleCalendarAuth extends Command
{
    protected $signature = 'laraclaw:google-auth';
    protected $description = 'Autoriza acesso ao Google Calendar via OAuth2';

    public function handle(): int
    {
        $service = app(GoogleCalendarService::class);
        $client = $service->getClient();

        $credentialsPath = config('laraclaw.google_calendar.credentials_path');

        if (!file_exists($credentialsPath)) {
            $this->error("Arquivo credentials.json nao encontrado em: {$credentialsPath}");
            $this->line('');
            $this->line('Para configurar:');
            $this->line('1. Acesse https://console.cloud.google.com');
            $this->line('2. Crie um projeto (ou use existente)');
            $this->line('3. Habilite "Google Calendar API"');
            $this->line('4. Crie credenciais OAuth2 (tipo "Desktop App")');
            $this->line('5. Baixe o JSON e salve em: ' . $credentialsPath);
            return self::FAILURE;
        }

        // Check if already authenticated
        if ($service->isAuthenticated()) {
            $this->info('Ja esta autenticado! Token valido.');
            if ($this->confirm('Deseja re-autenticar?', false)) {
                // Continue to auth flow
            } else {
                return self::SUCCESS;
            }
        }

        $authUrl = $client->createAuthUrl();

        $this->line('');
        $this->info('Abra a URL abaixo no navegador e autorize o acesso:');
        $this->line('');
        $this->line($authUrl);
        $this->line('');

        $code = $this->ask('Cole aqui o codigo de autorizacao');

        if (!$code) {
            $this->error('Codigo nao informado.');
            return self::FAILURE;
        }

        try {
            $token = $client->fetchAccessTokenWithAuthCode(trim($code));

            if (isset($token['error'])) {
                $this->error("Erro na autenticacao: {$token['error']}");
                return self::FAILURE;
            }

            $service->saveToken($token);

            $this->info('Token salvo com sucesso!');
            $this->line('Path: ' . config('laraclaw.google_calendar.token_path'));
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Erro: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
