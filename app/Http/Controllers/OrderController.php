<?php

namespace App\Http\Controllers;

use App\Facades\Analytics;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Handle online checkout and place a new order.
     * Demonstrates server-side analytics tracking via AnalyticsTracker helper.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:100',
            'customer_email' => 'required|email|max:100',
            'customer_phone' => 'nullable|string|max:20',
            'table_number' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1|max:50',
            'anonymous_id' => 'nullable|string|max:64',
            'session_id' => 'nullable|string|max:64',
        ]);

        $anonymousId = $validated['anonymous_id'] 
            ?? $request->header('X-Anonymous-Id') 
            ?? $request->cookie('ka_aid') 
            ?? (string) Str::uuid();

        $sessionId = $validated['session_id'] 
            ?? $request->header('X-Session-Id') 
            ?? $request->cookie('ka_sid') 
            ?? (string) Str::uuid();

        // Process order within database transaction
        $order = DB::transaction(function () use ($validated, $anonymousId, $sessionId) {
            $itemIds = collect($validated['items'])->pluck('id');
            $menuItems = MenuItem::whereIn('id', $itemIds)->get()->keyBy('id');

            $totalAmount = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $menuItem = $menuItems->get($item['id']);
                if (!$menuItem) continue;

                $qty = (int) $item['quantity'];
                $subtotal = $menuItem->price * $qty;
                $totalAmount += $subtotal;

                $itemsData[] = [
                    'menu_item_id' => $menuItem->id,
                    'item_name' => $menuItem->name,
                    'quantity' => $qty,
                    'unit_price' => $menuItem->price,
                    'subtotal' => $subtotal,
                ];
            }

            // Create Order
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'table_number' => $validated['table_number'] ?? 'Takeaway',
                'total_amount' => $totalAmount,
                'status' => 'completed',
                'anonymous_id' => $anonymousId,
                'session_id' => $sessionId,
            ]);

            // Create Order Items
            foreach ($itemsData as $data) {
                $order->items()->create($data);
            }

            return $order;
        });

        // ==========================================
        // 🚀 SERVER-SIDE ANALYTICS TRACKING
        // ==========================================
        // Track the critical business event from the server
        Analytics::track(
            eventName: 'order_created',
            properties: [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => (float) $order->total_amount,
                'items_count' => $order->items->sum('quantity'),
                'distinct_items' => $order->items->count(),
                'customer_email' => $order->customer_email,
                'table_number' => $order->table_number,
                'currency' => 'USD',
            ],
            userId: $order->customer_email,
            anonymousId: $anonymousId,
            sessionId: $sessionId,
            path: '/orders/checkout'
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Order placed successfully and recorded via Server Analytics SDK!',
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'total_amount' => number_format($order->total_amount, 2),
                'items_count' => $order->items->sum('quantity'),
                'created_at' => $order->created_at->toFormattedDateString(),
            ]
        ], 201);
    }
}

