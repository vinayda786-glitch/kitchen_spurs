<?php

namespace App\Facades;

use App\Services\Analytics\AnalyticsTracker;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\AnalyticsEvent|null track(string $eventName, array $properties = [], ?string $userId = null, ?string $anonymousId = null, ?string $sessionId = null, ?string $url = null, ?string $path = null, ?\Carbon\Carbon $occurredAt = null)
 * @method static \App\Models\AnalyticsEvent|null identify(string $userId, array $traits = [])
 *
 * @see \App\Services\Analytics\AnalyticsTracker
 */
class Analytics extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AnalyticsTracker::class;
    }
}

