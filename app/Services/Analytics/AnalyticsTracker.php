<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class AnalyticsTracker
{
    /**
     * Track a server-side business event.
     *
     * @param string $eventName Event identifier, e.g. 'order_created', 'user_registered'
     * @param array<string, mixed> $properties Arbitrary business payload
     * @param string|null $userId Optional identified user ID
     * @param string|null $anonymousId Optional client anonymous ID
     * @param string|null $sessionId Optional client session ID
     * @param string|null $url Optional current URL
     * @param string|null $path Optional path
     * @param Carbon|null $occurredAt Event timestamp
     * @return AnalyticsEvent|null
     */
    public function track(
        string $eventName,
        array $properties = [],
        ?string $userId = null,
        ?string $anonymousId = null,
        ?string $sessionId = null,
        ?string $url = null,
        ?string $path = null,
        ?Carbon $occurredAt = null
    ): ?AnalyticsEvent {
        try {
            // Automatic contextual fallbacks
            $userId = $userId ?? (Auth::check() ? (string) Auth::id() : null);
            $anonymousId = $anonymousId 
                ?? Request::header('X-Anonymous-Id') 
                ?? Request::cookie('ka_aid') 
                ?? (string) Str::uuid();

            $sessionId = $sessionId 
                ?? Request::header('X-Session-Id') 
                ?? Request::cookie('ka_sid') 
                ?? (string) Str::uuid();

            $url = $url ?? Request::fullUrl();
            $path = $path ?? ('/' . ltrim(Request::path(), '/'));
            $referrer = Request::header('referer');
            $occurredAt = $occurredAt ?? Carbon::now();

            $context = [
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent() ?? 'Server/PHP',
                'runtime' => 'PHP ' . PHP_VERSION,
                'server_time' => now()->toIso8601String(),
            ];

            return AnalyticsEvent::create([
                'id' => (string) Str::uuid(),
                'event_name' => trim($eventName),
                'event_source' => 'server',
                'anonymous_id' => $anonymousId,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'url' => $url,
                'path' => $path,
                'referrer' => $referrer,
                'properties' => $properties,
                'context' => $context,
                'occurred_at' => $occurredAt,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Resilience: Analytics failure should never break mission-critical application flows
            Log::error('Analytics Server Tracking failed: ' . $e->getMessage(), [
                'event' => $eventName,
                'exception' => $e,
            ]);
            return null;
        }
    }

    /**
     * Associate an identified user with traits or aliases.
     */
    public function identify(string $userId, array $traits = []): ?AnalyticsEvent
    {
        return $this->track('user_identified', [
            'traits' => $traits,
        ], $userId);
    }
}

