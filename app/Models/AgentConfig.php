<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentConfig extends Model
{
    protected $fillable = [
        'slug',
        'is_active',
        'cron_expression',
        'last_run_at',
        'next_run_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function executions(): HasMany
    {
        return $this->hasMany(Execution::class, 'agent_slug', 'slug');
    }
}
