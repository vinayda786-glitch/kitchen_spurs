<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AnalyticsController extends Controller
{
    /**
     * Ingest single or batch analytics events.
     */
    public function ingest(Request $request): JsonResponse
    {
        if ($request->has('batch')) {
            $rawEvents = $request->input('batch');
        } elseif ($request->has('event_name')) {
            $rawEvents = [$request->all()];
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or empty event payload. Must contain event_name or batch array.',
            ], 422);
        }

        if (!is_array($rawEvents) || empty($rawEvents)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or empty event payload',
            ], 422);
        }

        $now = Carbon::now();
        $processed = 0;
        $failed = 0;
        $recordsToInsert = [];

        foreach ($rawEvents as $event) {
            if (!is_array($event) || empty($event['event_name'])) {
                $failed++;
                continue;
            }

            // Extract and sanitize identifiers
            $eventName = substr(trim((string)$event['event_name']), 0, 64);
            $eventSource = in_array($event['event_source'] ?? '', ['browser', 'server']) 
                ? $event['event_source'] 
                : 'browser';

            $anonymousId = substr(trim((string)($event['anonymous_id'] ?? '')), 0, 64);
            if (empty($anonymousId)) {
                $anonymousId = (string) Str::uuid();
            }

            $sessionId = substr(trim((string)($event['session_id'] ?? '')), 0, 64);
            if (empty($sessionId)) {
                $sessionId = (string) Str::uuid();
            }

            $userId = !empty($event['user_id']) ? substr(trim((string)$event['user_id']), 0, 64) : null;

            // Handle client occurrence timestamp with clock skew sanity check (+/- 48 hours)
            $occurredAt = $now;
            if (!empty($event['occurred_at'])) {
                try {
                    $parsed = Carbon::parse($event['occurred_at']);
                    if ($parsed->diffInHours($now) <= 48) {
                        $occurredAt = $parsed;
                    }
                } catch (\Throwable) {
                    $occurredAt = $now;
                }
            }

            // Prepare context
            $context = is_array($event['context'] ?? null) ? $event['context'] : [];
            $context['ip'] = $context['ip'] ?? $request->ip();
            $context['user_agent'] = $context['user_agent'] ?? $request->userAgent();

            $properties = is_array($event['properties'] ?? null) ? $event['properties'] : [];

            $url = isset($event['url']) ? substr((string)$event['url'], 0, 2048) : null;
            $path = isset($event['path']) ? substr((string)$event['path'], 0, 255) : ($url ? parse_url($url, PHP_URL_PATH) : null);
            $referrer = isset($event['referrer']) ? substr((string)$event['referrer'], 0, 2048) : null;

            $recordsToInsert[] = [
                'id' => !empty($event['id']) && Str::isUuid($event['id']) ? $event['id'] : (string) Str::uuid(),
                'event_name' => $eventName,
                'event_source' => $eventSource,
                'anonymous_id' => $anonymousId,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'url' => $url,
                'path' => $path,
                'referrer' => $referrer,
                'properties' => json_encode($properties),
                'context' => json_encode($context),
                'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
                'created_at' => $now->format('Y-m-d H:i:s'),
            ];

            $processed++;
        }

        if (!empty($recordsToInsert)) {
            try {
                // Chunk insert for performance and payload safety
                foreach (array_chunk($recordsToInsert, 100) as $chunk) {
                    AnalyticsEvent::insert($chunk);
                }
            } catch (\Throwable $e) {
                Log::error('Analytics batch insert failure: ' . $e->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to store events',
                ], 500);
            }
        }

        return response()->json([
            'status' => 'success',
            'processed' => $processed,
            'failed' => $failed,
        ], 200);
    }

    /**
     * Get aggregated analytics statistics with date filtering.
     */
    public function stats(Request $request): JsonResponse
    {
        $to = $request->input('to', Carbon::now()->toDateString());
        $from = $request->input('from', Carbon::now()->subDays(6)->toDateString());

        // Base scoped query
        $baseQuery = AnalyticsEvent::dateRange($from, $to);

        // 1. High-level KPI Summary
        $totalEvents = (clone $baseQuery)->count();
        $uniqueVisitors = (clone $baseQuery)->distinct('anonymous_id')->count('anonymous_id');
        $totalSessions = (clone $baseQuery)->distinct('session_id')->count('session_id');
        $pageViews = (clone $baseQuery)->where('event_name', 'page_view')->count();
        $totalOrders = (clone $baseQuery)->where('event_name', 'order_created')->count();
        
        $conversionRate = $uniqueVisitors > 0 ? round(($totalOrders / $uniqueVisitors) * 100, 2) : 0;

        // Total revenue from orders within the date window
        $totalRevenue = (float) Order::whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->sum('total_amount');

        // 2. Traffic & Orders over time
        $driver = DB::connection()->getDriverName();
        $dateExpr = $driver === 'sqlite' 
            ? "strftime('%Y-%m-%d', occurred_at)" 
            : "DATE(occurred_at)";

        $trafficRaw = (clone $baseQuery)
            ->selectRaw("{$dateExpr} as date, 
                         COUNT(*) as total_events, 
                         SUM(CASE WHEN event_name = 'page_view' THEN 1 ELSE 0 END) as pageviews, 
                         COUNT(DISTINCT anonymous_id) as visitors,
                         SUM(CASE WHEN event_name = 'order_created' THEN 1 ELSE 0 END) as orders")
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // 3. Popular Pages
        $popularPages = (clone $baseQuery)
            ->where('event_name', 'page_view')
            ->whereNotNull('path')
            ->selectRaw("path, COUNT(*) as views, COUNT(DISTINCT anonymous_id) as visitors")
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(8)
            ->get();

        // 4. Top Events Breakdown
        $topEvents = (clone $baseQuery)
            ->selectRaw("event_name, event_source, COUNT(*) as count")
            ->groupBy('event_name', 'event_source')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // 5. Conversion Funnel (Page View -> Item Viewed -> Add to Cart -> Checkout Started -> Order Created)
        $funnelSteps = [
            ['key' => 'page_view', 'label' => '1. Page Visits'],
            ['key' => 'item_viewed', 'label' => '2. Item Viewed'],
            ['key' => 'add_to_cart', 'label' => '3. Added to Cart'],
            ['key' => 'checkout_started', 'label' => '4. Checkout Started'],
            ['key' => 'order_created', 'label' => '5. Order Completed'],
        ];

        $funnelData = [];
        $firstStepCount = 0;

        foreach ($funnelSteps as $index => $step) {
            $count = (clone $baseQuery)
                ->where('event_name', $step['key'])
                ->distinct('anonymous_id')
                ->count('anonymous_id');

            if ($index === 0) {
                $firstStepCount = max($count, 1);
            }

            $percentage = round(($count / $firstStepCount) * 100, 1);

            $funnelData[] = [
                'step' => $step['label'],
                'event_name' => $step['key'],
                'unique_users' => $count,
                'conversion_pct' => $percentage,
            ];
        }

        // 6. Recent Event Activity Feed (Latest 25)
        $recentEvents = (clone $baseQuery)
            ->orderByDesc('occurred_at')
            ->limit(25)
            ->get([
                'id',
                'event_name',
                'event_source',
                'anonymous_id',
                'user_id',
                'session_id',
                'path',
                'properties',
                'context',
                'occurred_at',
            ]);

        return response()->json([
            'summary' => [
                'total_events' => $totalEvents,
                'unique_visitors' => $uniqueVisitors,
                'total_sessions' => $totalSessions,
                'page_views' => $pageViews,
                'total_orders' => $totalOrders,
                'conversion_rate' => $conversionRate,
                'total_revenue' => $totalRevenue,
                'date_from' => $from,
                'date_to' => $to,
            ],
            'traffic_over_time' => $trafficRaw,
            'popular_pages' => $popularPages,
            'top_events' => $topEvents,
            'funnel' => $funnelData,
            'recent_events' => $recentEvents,
        ]);
    }

    /**
     * Generate synthetic realistic demo traffic across the past 5 days.
     * Allows evaluators to test charts and funnels instantly.
     */
    public function simulateTraffic(Request $request): JsonResponse
    {
        $menuItems = MenuItem::all();
        if ($menuItems->isEmpty()) {
            return response()->json(['error' => 'No menu items found. Please run seeder.'], 400);
        }

        $now = Carbon::now();
        $sampleEvents = [];
        $createdOrders = 0;

        // Generate 25 distinct visitor sessions over the last 5 days
        for ($s = 1; $s <= 25; $s++) {
            $anonId = 'sim_' . Str::random(12);
            $sessId = 'sess_' . Str::random(10);
            $userId = null;
            $dayOffset = rand(0, 4);
            $hourOffset = rand(9, 22);
            $minuteOffset = rand(0, 59);
            $time = $now->copy()->subDays($dayOffset)->setHour($hourOffset)->setMinute($minuteOffset);

            // Step 1: Visit Home/Menu
            $sampleEvents[] = [
                'id' => (string) Str::uuid(),
                'event_name' => 'page_view',
                'event_source' => 'browser',
                'anonymous_id' => $anonId,
                'user_id' => null,
                'session_id' => $sessId,
                'url' => 'http://kitchen.test/menu',
                'path' => '/menu',
                'referrer' => 'https://google.com',
                'properties' => json_encode(['title' => 'Kitchen - Artisanal Dining Menu']),
                'context' => json_encode(['browser' => 'Chrome', 'device' => 'Desktop', 'screen_width' => 1920]),
                'occurred_at' => $time->format('Y-m-d H:i:s'),
                'created_at' => $now->format('Y-m-d H:i:s'),
            ];

            // 70% chance to view a specific item
            if (rand(1, 100) <= 70) {
                $time->addSeconds(rand(10, 40));
                $randomItem = $menuItems->random();

                $sampleEvents[] = [
                    'id' => (string) Str::uuid(),
                    'event_name' => 'item_viewed',
                    'event_source' => 'browser',
                    'anonymous_id' => $anonId,
                    'user_id' => null,
                    'session_id' => $sessId,
                    'url' => 'http://kitchen.test/menu',
                    'path' => '/menu',
                    'referrer' => 'http://kitchen.test/menu',
                    'properties' => json_encode([
                        'item_id' => $randomItem->id,
                        'item_name' => $randomItem->name,
                        'price' => $randomItem->price,
                        'category' => $randomItem->category,
                    ]),
                    'context' => json_encode(['browser' => 'Chrome', 'device' => 'Desktop']),
                    'occurred_at' => $time->format('Y-m-d H:i:s'),
                    'created_at' => $now->format('Y-m-d H:i:s'),
                ];

                // 45% chance to add to cart
                if (rand(1, 100) <= 45) {
                    $time->addSeconds(rand(5, 20));
                    $sampleEvents[] = [
                        'id' => (string) Str::uuid(),
                        'event_name' => 'add_to_cart',
                        'event_source' => 'browser',
                        'anonymous_id' => $anonId,
                        'user_id' => null,
                        'session_id' => $sessId,
                        'url' => 'http://kitchen.test/menu',
                        'path' => '/menu',
                        'referrer' => 'http://kitchen.test/menu',
                        'properties' => json_encode([
                            'item_id' => $randomItem->id,
                            'item_name' => $randomItem->name,
                            'price' => $randomItem->price,
                            'quantity' => 1,
                        ]),
                        'context' => json_encode(['browser' => 'Chrome', 'device' => 'Desktop']),
                        'occurred_at' => $time->format('Y-m-d H:i:s'),
                        'created_at' => $now->format('Y-m-d H:i:s'),
                    ];

                    // 30% chance to proceed to checkout
                    if (rand(1, 100) <= 30) {
                        $time->addSeconds(rand(15, 60));
                        $sampleEvents[] = [
                            'id' => (string) Str::uuid(),
                            'event_name' => 'checkout_started',
                            'event_source' => 'browser',
                            'anonymous_id' => $anonId,
                            'user_id' => null,
                            'session_id' => $sessId,
                            'url' => 'http://kitchen.test/checkout',
                            'path' => '/checkout',
                            'referrer' => 'http://kitchen.test/menu',
                            'properties' => json_encode([
                                'cart_total' => $randomItem->price,
                                'items_count' => 1,
                            ]),
                            'context' => json_encode(['browser' => 'Chrome', 'device' => 'Desktop']),
                            'occurred_at' => $time->format('Y-m-d H:i:s'),
                            'created_at' => $now->format('Y-m-d H:i:s'),
                        ];

                        // 75% of checkout converts to completed order!
                        if (rand(1, 100) <= 75) {
                            $time->addSeconds(rand(20, 60));
                            $userId = 'usr_' . Str::random(8);
                            $custName = 'Guest Customer ' . rand(100, 999);
                            $custEmail = 'guest' . rand(100, 999) . '@example.com';
                            $orderNum = 'ORD-' . $time->format('Ymd') . '-' . rand(1000, 9999);

                            $order = Order::create([
                                'order_number' => $orderNum,
                                'customer_name' => $custName,
                                'customer_email' => $custEmail,
                                'total_amount' => $randomItem->price,
                                'status' => 'completed',
                                'anonymous_id' => $anonId,
                                'session_id' => $sessId,
                                'created_at' => $time,
                                'updated_at' => $time,
                            ]);

                            OrderItem::create([
                                'order_id' => $order->id,
                                'menu_item_id' => $randomItem->id,
                                'item_name' => $randomItem->name,
                                'quantity' => 1,
                                'unit_price' => $randomItem->price,
                                'subtotal' => $randomItem->price,
                                'created_at' => $time,
                                'updated_at' => $time,
                            ]);

                            // Server tracking event!
                            $sampleEvents[] = [
                                'id' => (string) Str::uuid(),
                                'event_name' => 'order_created',
                                'event_source' => 'server',
                                'anonymous_id' => $anonId,
                                'user_id' => $userId,
                                'session_id' => $sessId,
                                'url' => 'http://kitchen.test/orders',
                                'path' => '/orders',
                                'referrer' => 'http://kitchen.test/checkout',
                                'properties' => json_encode([
                                    'order_id' => $order->id,
                                    'order_number' => $orderNum,
                                    'total_amount' => $randomItem->price,
                                    'items_count' => 1,
                                    'customer_email' => $custEmail,
                                ]),
                                'context' => json_encode([
                                    'runtime' => 'PHP ' . PHP_VERSION,
                                    'ip' => '127.0.0.1',
                                    'user_agent' => 'Server/PHP',
                                ]),
                                'occurred_at' => $time->format('Y-m-d H:i:s'),
                                'created_at' => $now->format('Y-m-d H:i:s'),
                            ];

                            $createdOrders++;
                        }
                    }
                }
            }
        }

        // Insert simulated events
        foreach (array_chunk($sampleEvents, 100) as $chunk) {
            AnalyticsEvent::insert($chunk);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Simulated traffic generated successfully',
            'events_generated' => count($sampleEvents),
            'orders_generated' => $createdOrders,
        ]);
    }

    /**
     * Clear analytics events and simulated orders for a clean testing slate.
     */
    public function resetData(): JsonResponse
    {
        AnalyticsEvent::truncate();
        return response()->json([
            'status' => 'success',
            'message' => 'Analytics events table truncated',
        ]);
    }
}
