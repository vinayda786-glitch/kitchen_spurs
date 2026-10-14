<?php

namespace Tests\Unit;

use App\Models\AnalyticsEvent;
use App\Models\MenuItem;
use App\Models\Order;
use App\Services\Analytics\AnalyticsTracker;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsTrackerUnitTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsTracker $tracker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tracker = new AnalyticsTracker();
    }

    /**
     * Test server tracker records event with correct attributes.
     */
    public function test_tracker_records_event_with_properties(): void
    {
        $event = $this->tracker->track(
            eventName: 'kitchen_order_paid',
            properties: [
                'order_id' => 999,
                'amount' => 45.50,
                'currency' => 'USD',
            ],
            userId: 'user_dev_01',
            anonymousId: 'anon_dev_01',
            sessionId: 'sess_dev_01'
        );

        $this->assertInstanceOf(AnalyticsEvent::class, $event);
        $this->assertEquals('kitchen_order_paid', $event->event_name);
        $this->assertEquals('server', $event->event_source);
        $this->assertEquals(45.50, $event->properties['amount']);
        $this->assertEquals('USD', $event->properties['currency']);
        $this->assertEquals('user_dev_01', $event->user_id);
        $this->assertTrue(Str::isUuid($event->id));
    }

    /**
     * Test tracker auto-generates UUIDs when anonymous_id and session_id are omitted.
     */
    public function test_tracker_generates_uuids_when_identifiers_are_null(): void
    {
        $event = $this->tracker->track('anonymous_test_action');

        $this->assertNotNull($event);
        $this->assertTrue(Str::isUuid($event->anonymous_id));
        $this->assertTrue(Str::isUuid($event->session_id));
        $this->assertNull($event->user_id);
    }

    /**
     * Test identify method tracks user_identified event.
     */
    public function test_tracker_identify_creates_user_identified_event(): void
    {
        $event = $this->tracker->identify('customer@chef.com', [
            'name' => 'Gordon Ramsay',
            'role' => 'VIP Diner',
        ]);

        $this->assertNotNull($event);
        $this->assertEquals('user_identified', $event->event_name);
        $this->assertEquals('customer@chef.com', $event->user_id);
        $this->assertEquals('Gordon Ramsay', $event->properties['traits']['name']);
    }

    /**
     * Test AnalyticsEvent model query scopes.
     */
    public function test_analytics_event_scopes(): void
    {
        AnalyticsEvent::create([
            'id' => (string) Str::uuid(),
            'event_name' => 'page_view',
            'event_source' => 'browser',
            'anonymous_id' => 'scope_anon_1',
            'session_id' => 'scope_sess_1',
            'occurred_at' => Carbon::now()->subDays(2),
            'created_at' => Carbon::now(),
        ]);

        AnalyticsEvent::create([
            'id' => (string) Str::uuid(),
            'event_name' => 'order_created',
            'event_source' => 'server',
            'anonymous_id' => 'scope_anon_2',
            'session_id' => 'scope_sess_2',
            'occurred_at' => Carbon::now()->subDays(10), // Out of 7-day range
            'created_at' => Carbon::now(),
        ]);

        $inRange = AnalyticsEvent::dateRange(
            Carbon::now()->subDays(5)->toDateString(),
            Carbon::now()->toDateString()
        )->get();

        $this->assertCount(1, $inRange);
        $this->assertEquals('page_view', $inRange->first()->event_name);
    }

    /**
     * Test MenuItem model casting and pricing.
     */
    public function test_menu_item_model_casting(): void
    {
        $item = MenuItem::create([
            'name' => 'Truffle Risotto',
            'category' => 'Mains',
            'price' => 19.50,
            'is_available' => true,
        ]);

        $this->assertIsFloat((float) $item->price);
        $this->assertEquals(19.50, $item->price);
        $this->assertTrue($item->is_available);
    }

    /**
     * Test Order and OrderItem relationship and calculations.
     */
    public function test_order_relationship_and_calculations(): void
    {
        $item = MenuItem::create([
            'name' => 'Espresso',
            'category' => 'Beverages',
            'price' => 3.50,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-UNIT-TEST-1',
            'customer_name' => 'Alice Wonder',
            'customer_email' => 'alice@test.com',
            'total_amount' => 7.00,
            'anonymous_id' => (string) Str::uuid(),
            'session_id' => (string) Str::uuid(),
        ]);

        $order->items()->create([
            'menu_item_id' => $item->id,
            'item_name' => $item->name,
            'quantity' => 2,
            'unit_price' => 3.50,
            'subtotal' => 7.00,
        ]);

        $this->assertCount(1, $order->items);
        $this->assertEquals(7.00, $order->items->first()->subtotal);
        $this->assertEquals('alice@test.com', $order->customer_email);
    }
}
