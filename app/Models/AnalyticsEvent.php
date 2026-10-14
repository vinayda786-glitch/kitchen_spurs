<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'event_name',
        'event_source',
        'anonymous_id',
        'user_id',
        'session_id',
        'url',
        'path',
        'referrer',
        'properties',
        'context',
        'occurred_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'context' => 'array',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Scope a query to only include events within a given date range.
     */
    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where('occurred_at', '>=', $from . ' 00:00:00');
        }

        if ($to) {
            $query->where('occurred_at', '<=', $to . ' 23:59:59');
        }

        return $query;
    }

    /**
     * Scope for a specific event name.
     */
    public function scopeEvent(Builder $query, string $name): Builder
    {
        return $query->where('event_name', $name);
    }
}

