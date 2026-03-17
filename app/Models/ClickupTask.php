<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClickupTask extends Model
{
    protected $fillable = [
        'clickup_id',
        'name',
        'status',
        'status_color',
        'assignee_id',
        'assignee_name',
        'priority',
        'project',
        'due_date',
        'date_created',
        'date_done',
        'url',
        'tags',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'date_created' => 'datetime',
            'date_done' => 'datetime',
            'tags' => 'array',
        ];
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && !$this->date_done;
    }
}
