<?php

namespace Tests\Feature;

use App\Facades\Analytics;
use App\Models\AnalyticsEvent;
use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsPipelineTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the Restaurant home menu page loads successfully.
     */
    public function test_restaurant_menu_page_renders_successfully(): void
    {
        MenuItem::create([
            'name' => 'Signature Truffle Pasta',
            'category' => 'Mains',
            'price' => 18.00,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Kitchen');
        $response->assertSee('kitchen-analytics.js');
    }

    /**
     * Test that the Analytics Dashboard page loads successfully.
     */
    public function test_analytics_dashboard_page_renders_successfully(): void
    {
        $response = $this->get('/analytics');
        $response->assertStatus(200);
        $response->assertSee('Traffic & Conversion Trend', false);
        $response->assertSee('Raw Event Ingestion Stream');
    }

    /**
     * Test single event ingestion via API.
     */
    public function test_ingestion_api_accepts_valid_single_event(): void
    {
        $payload = [
            'event_name' => 'page_view',
            'event_source' => 'browser',
            'anonymous_id' => 'test-anon-12345',
            'session_id' => 'test-sess-99999',
            'path' => '/menu',
            'url' => 'http://localhost/menu',
            'properties' => [
                'title' => 'Menu - Kitchen',
            ],
            'context' => [
                'screen_width' => 1920,
                'screen_height' => 1080,
            ],
            'occurred_at' => now()->toISOString(),
        ];

        $response = $this->postJson('/api/v1/analytics/events', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'processed' => 1,
                'failed' => 0,
            ]);

        $this->assertDatabaseHas('analytics_events', [
            'event_name' => 'page_view',
            'anonymous_id' => 'test-anon-12345',
            'path' => '/menu',
        ]);
    }

    /**
     * Test batch events ingestion via API.
     */
    public function test_ingestion_api_accepts_batch_events(): void
    {
        $payload = [
            'batch' => [
                [
                    'event_name' => 'item_viewed',
                    'anonymous_id' => 'batch-anon-1',
                    'session_id' => 'batch-sess-1',
                    'path' => '/menu',
                    'properties' => ['item_name' => 'Margherita Pizza'],
                ],
                [
                    'event_name' => 'add_to_cart',
                    'anonymous_id' => 'batch-anon-1',
                    'session_id' => 'batch-sess-1',
                    'path' => '/menu',
                    'properties' => ['item_name' => 'Margherita Pizza', 'price' => 14.50],
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/analytics/events', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'processed' => 2,
                'failed' => 0,
            ]);

        $this->assertDatabaseHas('analytics_events', [
            'event_name' => 'add_to_cart',
            'anonymous_id' => 'batch-anon-1',
        ]);
    }

    /**
     * Test ingestion API gracefully rejects empty payload.
     */
    public function test_ingestion_api_rejects_empty_payload(): void
    {
        $response = $this->postJson('/api/v1/analytics/events', []);
        $response->assertStatus(422)
            ->assertJson(['status' => 'error']);
    }

    /**
     * Test Server Tracking Helper / Facade.
     */
    public function test_server_tracker_helper_creates_event_safely(): void
    {
        $event = Analytics::track(
            eventName: 'user_subscribed',
            properties: ['plan' => 'VIP Club', 'discount' => 10],
            userId: 'vip_user_44',
            anonymousId: 'anon_vip_11',
            sessionId: 'sess_vip_22'
        );

        $this->assertNotNull($event);
        $this->assertEquals('server', $event->event_source);
        $this->assertEquals('user_subscribed', $event->event_name);
        $this->assertEquals('vip_user_44', $event->user_id);

        $this->assertDatabaseHas('analytics_events', [
            'event_name' => 'user_subscribed',
            'event_source' => 'server',
            'user_id' => 'vip_user_44',
        ]);
    }

    /**
     * Test complete Order Checkout Flow:
     * Placing an order records order in DB and fires the Server Analytics SDK event.
     */
    public function test_order_checkout_flow_creates_order_and_triggers_server_analytics_event(): void
    {
        $item = MenuItem::first() ?? MenuItem::create([
            'name' => 'Test Burger',
            'category' => 'Mains',
            'price' => 15.00,
        ]);

        $orderPayload = [
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane.doe@example.com',
            'customer_phone' => '555-1234',
            'table_number' => 'Table 7',
            'items' => [
                ['id' => $item->id, 'quantity' => 2]
            ],
            'anonymous_id' => 'checkout-anon-777',
            'session_id' => 'checkout-sess-888',
        ];

        $response = $this->postJson('/orders', $orderPayload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
            ]);

        // 1. Verify Order in database
        $this->assertDatabaseHas('orders', [
            'customer_email' => 'jane.doe@example.com',
            'anonymous_id' => 'checkout-anon-777',
        ]);

        // 2. Verify Server SDK automatically tracked order_created event
        $this->assertDatabaseHas('analytics_events', [
            'event_name' => 'order_created',
            'event_source' => 'server',
            'anonymous_id' => 'checkout-anon-777',
            'user_id' => 'jane.doe@example.com',
        ]);
    }

    /**
     * Test Dashboard Stats API aggregation with date filters.
     */
    public function test_dashboard_stats_api_returns_expected_kpis_and_funnel(): void
    {
        $response = $this->getJson('/api/v1/analytics/stats?from=' . now()->subDays(7)->toDateString() . '&to=' . now()->toDateString());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'summary' => [
                    'total_events',
                    'unique_visitors',
                    'total_sessions',
                    'page_views',
                    'total_orders',
                    'conversion_rate',
                    'total_revenue',
                ],
                'traffic_over_time',
                'popular_pages',
                'top_events',
                'funnel',
                'recent_events',
            ]);
    }

    /**
     * Test traffic simulation endpoint.
     */
    public function test_simulate_traffic_generates_events_and_orders(): void
    {
        MenuItem::create([
            'name' => 'Signature Truffle Pasta',
            'category' => 'Mains',
            'price' => 18.00,
        ]);

        $response = $this->postJson('/api/v1/analytics/simulate');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertGreaterThan(0, AnalyticsEvent::count());
    }
}

