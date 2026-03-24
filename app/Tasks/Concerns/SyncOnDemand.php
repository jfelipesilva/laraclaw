<?php

namespace App\Tasks\Concerns;

trait SyncOnDemand
{
    public function getCronExpression(): ?string
    {
        return null;
    }

    public function shouldSync(int $cooldownSeconds = 180): bool
    {
        $config = $this->getConfig();

        if (!$config || !$config->last_run_at) {
            return true;
        }

        return $config->last_run_at->diffInSeconds(now()) >= $cooldownSeconds;
    }
}
