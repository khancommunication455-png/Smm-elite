<?php

namespace App\Http\Controllers;

use App\Jobs\Payments\ProcessPaymentWebhook;
use App\Models\PaymentWebhookEvent;
use App\Services\Payments\PaymentWebhookVerifier;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Verifies and stores payment webhooks before queueing ledger work.
 * SECURITY: no payment data is trusted until gateway signatures are verified.
 * SCALABILITY: verified events are persisted then processed by Redis workers.
 */
class WebhookController extends Controller
{
    public function __construct(private readonly PaymentWebhookVerifier $verifier)
    {
    }

    public function stripe(Request $request): Response
    {
        try {
            $event = $this->verifier->verifyStripe($request);
        } catch (Throwable $e) {
            Log::warning('Rejected Stripe webhook.', ['ip' => $request->ip(), 'error' => $e->getMessage()]);
            return response('Invalid Stripe webhook.', 400);
        }

        $storedEvent = $this->storeEvent(
            gateway: 'stripe',
            gatewayEventId: (string) $event->id,
            eventType: (string) $event->type,
            payload: method_exists($event, 'toArray') ? $event->toArray() : json_decode(json_encode($event), true)
        );

        ProcessPaymentWebhook::dispatch($storedEvent->id);

        return response('OK', 200);
    }

    public function paypal(Request $request): Response
    {
        try {
            $payload = $this->verifier->verifyPayPal($request);
        } catch (Throwable $e) {
            Log::warning('Rejected PayPal webhook.', ['ip' => $request->ip(), 'error' => $e->getMessage()]);
            return response('Invalid PayPal webhook.', 400);
        }

        $storedEvent = $this->storeEvent(
            gateway: 'paypal',
            gatewayEventId: (string) ($payload['id'] ?? $request->header('PAYPAL-TRANSMISSION-ID')),
            eventType: (string) ($payload['event_type'] ?? 'unknown'),
            payload: $payload
        );

        ProcessPaymentWebhook::dispatch($storedEvent->id);

        return response('OK', 200);
    }

    private function storeEvent(string $gateway, string $gatewayEventId, string $eventType, array $payload): PaymentWebhookEvent
    {
        try {
            $event = PaymentWebhookEvent::firstOrCreate(
                [
                    'gateway' => $gateway,
                    'gateway_event_id' => $gatewayEventId,
                ],
                [
                    'event_type' => $eventType,
                    'status' => 'pending',
                    'payload' => $payload,
                    'attempts' => 0,
                ]
            );
        } catch (UniqueConstraintViolationException) {
            $event = PaymentWebhookEvent::where('gateway', $gateway)
                ->where('gateway_event_id', $gatewayEventId)
                ->firstOrFail();
        }

        if (! $event->wasRecentlyCreated) {
            Log::info('Duplicate payment webhook received; existing event will not be duplicated.', [
                'gateway' => $gateway,
                'event_id' => $gatewayEventId,
                'status' => $event->status,
            ]);
        }

        return $event;
    }
}
