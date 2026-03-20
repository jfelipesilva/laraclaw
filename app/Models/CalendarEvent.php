<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    protected $fillable = [
        'google_event_id',
        'title',
        'description',
        'location',
        'start_at',
        'end_at',
        'all_day',
        'status',
        'calendar_name',
        'color',
        'html_link',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'all_day' => 'boolean',
        ];
    }

    public function isToday(): bool
    {
        return $this->start_at->isToday();
    }

    public function isNow(): bool
    {
        return now()->between($this->start_at, $this->end_at ?? $this->start_at);
    }
}
