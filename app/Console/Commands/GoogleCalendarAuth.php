<?php

namespace App\Console\Commands;

use App\Services\GoogleCalendarService;
use Illuminate\Console\Command;

class GoogleCalendarAuth extends Command
{
    protected $signature = 'laraclaw:google-auth';
    protected $description = 'Verifica status da autenticacao Google Calendar';

    public function handle(): int
    {
        $credentialsPath = config('laraclaw.google_calendar.credentials_path');

        if (!file_exists($credentialsPath)) {
            $this->error("Arquivo credentials.json nao encontrado em: {$credentialsPath}");
            return self::FAILURE;
        }

        $service = app(GoogleCalendarService::class);

        if ($service->isAuthenticated()) {
            $this->info('Google Calendar autenticado! Token valido.');
            return self::SUCCESS;
        }

        $this->warn('Google Calendar NAO autenticado.');
        $this->line('');
        $this->line('Acesse no navegador para autorizar:');
        $this->line(config('app.url') . '/google/auth');

        return self::FAILURE;
    }
}
