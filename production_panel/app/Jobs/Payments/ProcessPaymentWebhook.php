<?php

namespace App\Jobs\Payments;

use App\Models\PaymentWebhookEvent;
use App\Services\Payments\PaymentLedgerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Processes verified payment events asynchronously.
 * SCALABILITY: webhook requests ACK quickly while all expensive/lock-heavy
 * ledger work runs on Redis workers with retries and overlap protection.
 */
class ProcessPaymentWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 30;
    public int $maxExceptions = 3;
    public bool $afterCommit = true;

    public function __construct(public int $paymentWebhookEventId)
    {
        $this->onQueue('payments');
    }

    public function backoff(): array
    {
        return [10, 60, 300, 900, 1800];
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('payment-webhook-'.$this->paymentWebhookEventId))->expireAfter(300)];
    }

    public function handle(PaymentLedgerService $ledger): void
    {
        $event = DB::transaction(function (): ?PaymentWebhookEvent {
            $event = PaymentWebhookEvent::query()->lockForUpdate()->find($this->paymentWebhookEventId);

            if (! $event || $event->status === 'processed') {
                return null;
            }

            $event->markProcessing();

            return $event;
        });

        if (! $event) {
            return;
        }

        try {
            match ($event->gateway) {
                'stripe' => $this->processStripe($event, $ledger),
                'paypal' => $this->processPayPal($event, $ledger),
                default => Log::info('Ignoring unsupported payment gateway event.', [
                    'gateway' => $event->gateway,
                    'event_id' => $event->gateway_event_id,
                ]),
            };

            $event->markProcessed();
        } catch (Throwable $e) {
            $event->markFailed($e->getMessage());
            throw $e;
        }
    }

    private function processStripe(PaymentWebhookEvent $event, PaymentLedgerService $ledger): void
    {
        $payload = $event->payload;
        $intent = Arr::get($payload, 'data.object', []);
        $reference = (string) Arr::get($intent, 'id', $event->gateway_event_id);
        $userId = (int) Arr::get($intent, 'metadata.user_id');
        $amount = ((float) Arr::get($intent, 'amount_received', 0)) / 100;

        if ($event->event_type === 'payment_intent.succeeded') {
            $ledger->creditDeposit('stripe', $userId, $amount, $reference, $intent);
            return;
        }

        if ($event->event_type === 'payment_intent.payment_failed') {
            $ledger->recordFailure(
                'stripe',
                $userId ?: null,
                ((float) Arr::get($intent, 'amount', 0)) / 100,
                $reference,
                (string) Arr::get($intent, 'last_payment_error.message', 'Stripe payment failed.'),
                $intent
            );
        }
    }

    private function processPayPal(PaymentWebhookEvent $event, PaymentLedgerService $ledger): void
    {
        $payload = $event->payload;
        $resource = Arr::get($payload, 'resource', []);
        $reference = (string) (Arr::get($resource, 'id') ?: $event->gateway_event_id);
        $userId = (int) Arr::get($resource, 'custom_id', Arr::get($resource, 'purchase_units.0.custom_id'));
        $amount = (float) Arr::get($resource, 'amount.value', Arr::get($resource, 'purchase_units.0.amount.value', 0));

        if ($event->event_type === 'PAYMENT.CAPTURE.COMPLETED') {
            $ledger->creditDeposit('paypal', $userId, $amount, $reference, $resource);
            return;
        }

        if ($event->event_type === 'CHECKOUT.ORDER.APPROVED') {
            Log::info('PayPal order approved; waiting for capture completion before crediting funds.', [
                'event_id' => $event->gateway_event_id,
                'reference' => $reference,
            ]);
            return;
        }

        if (str_contains($event->event_type, 'FAILED') || str_contains($event->event_type, 'DENIED')) {
            $ledger->recordFailure('paypal', $userId ?: null, $amount, $reference, 'PayPal reported '.$event->event_type, $resource);
        }
    }
}
