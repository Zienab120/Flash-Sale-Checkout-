<?php

namespace App\Services;

use App\Models\Order;
use App\Models\WebhookIdempotencyKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public function __construct(private ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function handlePaymentWebhook($request)
    {
        $webhook_idempotency_key = WebhookIdempotencyKey::where('key', $request->idempotency_key)
            ->first();

        if ($webhook_idempotency_key && $webhook_idempotency_key->processed_at) {
            Log::channel('payment')->info(
                "Payment webhook already processed.",
                [
                    'idempotency_key' => $webhook_idempotency_key->key,
                    'response' => $webhook_idempotency_key->response,
                    'processed_at' => $webhook_idempotency_key->processed_at
                ]
            );

            return $webhook_idempotency_key->response;
        }

        return DB::transaction(function () use ($request, $webhook_idempotency_key) {
            $order = Order::where('id', $request->order_id)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                Log::channel('payment')->warning(
                    "Webhook arrived for order that does not exist.",
                    [
                        'order_id' => $request->order_id,
                        'response' => $webhook_idempotency_key->response,
                    ]
                );

                $response = [
                    'status' => 'accepted',
                    'message' => 'Webhook received, order not yet created',
                ];

                WebhookIdempotencyKey::create([
                    'key' => $request->idempotency_key,
                    'order_id' => $request->order_id,
                    'response' => $response,
                    'processed_at' => now(),
                ]);

                return $response;
            }

            $response = $this->updateOrderStatus($order, $request->status);

            WebhookIdempotencyKey::create([
                'key' => $request->idempotency_key,
                'order_id' => $order->id,
                'response' => $response,
                'processed_at' => now(),
            ]);

            return $response;
        });
    }

    private function updateOrderStatus($order, $webhookStatus)
    {
        if ($webhookStatus === 'success') {
            if ($order->status === 'prepayment') {
                $order->update(['status' => 'paid']);
                Log::channel('payment')
                    ->info('Order marked as paid', ['order_id' => $order->id]);

                return [
                    'status' => 'success',
                    'message' => 'Payment confirmed',
                    'order_status' => 'paid',
                ];
            } elseif ($order->status === 'paid') {
                Log::channel('payment')
                    ->info('Order already paid (idempotent)', ['order_id' => $order->id]);

                return [
                    'status' => 'success',
                    'message' => 'Payment already confirmed',
                    'order_status' => 'paid',
                ];
            }
        } elseif ($webhookStatus === 'failed') {
            if ($order->status === 'prepayment') {
                $order->update(['status' => 'cancelled']);

                $order->product->increment('available_stock', $order->quantity);
                $this->productService->forgetProductCache($order->product_id);

                Log::channel('order')
                    ->info('Order cancelled, stock released', [
                        'order_id' => $order->id,
                        'quantity_released' => $order->quantity,
                    ]);

                return [
                    'status' => 'success',
                    'message' => 'Payment failed, order cancelled',
                    'order_status' => 'cancelled',
                ];
            } elseif ($order->status === 'cancelled') {

                Log::channel('order')
                    ->info('Order already cancelled (idempotent)', ['order_id' => $order->id]);

                return [
                    'status' => 'success',
                    'message' => 'Order already cancelled',
                    'order_status' => 'cancelled',
                ];
            }
        }

        return [
            'status' => 'success',
            'message' => 'Order status unchanged',
            'order_status' => $order->status,
        ];
    }
}
