<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Execution extends Model
{
    protected $fillable = [
        'agent_slug',
        'status',
        'prompt',
        'system_prompt',
        'directory',
        'started_at',
        'finished_at',
        'duration_ms',
        'cost_usd',
        'num_turns',
        'session_id',
        'output_result',
        'error_log',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'cost_usd' => 'float',
        ];
    }
}
