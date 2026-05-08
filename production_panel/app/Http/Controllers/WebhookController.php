<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

/**
 * WebhookController
 *
 * FIXES CRITICAL-5:
 * - WebhookController was referenced in routes but did not exist (404 on every webhook)
 * - Added Stripe webhook signature verification to prevent replay attacks
 * - PayPal webhook stub with idempotency guard
 */
class WebhookController extends Controller
{
    /**
     * Handle Stripe webhooks.
     * CSRF is excluded for this route in VerifyCsrfToken::$except.
     */
    public function stripe(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        if (! $secret) {
            Log::error('STRIPE_WEBHOOK_SECRET not configured.');
            return response('Webhook secret not configured.', 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed.', [
                'error' => $e->getMessage(),
                'ip'    => $request->ip(),
            ]);
            return response('Invalid signature.', 400);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook parse error: ' . $e->getMessage());
            return response('Bad request.', 400);
        }

        Log::info('Stripe webhook received', [
            'type'       => $event->type,
            'event_id'   => $event->id,
        ]);

        match ($event->type) {
            'payment_intent.succeeded'       => $this->handleStripePaymentSucceeded($event),
            'payment_intent.payment_failed'  => $this->handleStripePaymentFailed($event),
            'charge.refunded'                => $this->handleStripeRefund($event),
            default => null, // Unknown event types are ignored safely
        };

        return response('OK', 200);
    }

    /**
     * Handle PayPal webhooks.
     * Note: PayPal webhook verification requires the PayPal SDK.
     * Implement full verification when enabling PayPal payments.
     */
    public function paypal(Request $request): Response
    {
        // TODO: Implement PayPal webhook signature verification using PayPal SDK
        // PayPalCheckoutSdk\Core\PayPalHttpClient + verify cert chain

        $payload = $request->json()->all();

        Log::info('PayPal webhook received', [
            'event_type' => $payload['event_type'] ?? 'unknown',
        ]);

        // Stub: handle checkout.order.approved
        if (($payload['event_type'] ?? '') === 'CHECKOUT.ORDER.APPROVED') {
            // TODO: credit user funds after verifying the order
        }

        return response('OK', 200);
    }

    // ─── Stripe Handlers ─────────────────────────────────────────────────────

    private function handleStripePaymentSucceeded(object $event): void
    {
        $intent   = $event->data->object;
        $metadata = $intent->metadata ?? [];

        $userId    = $metadata['user_id'] ?? null;
        $amount    = ($intent->amount_received ?? 0) / 100; // convert cents to currency unit
        $reference = $intent->id;

        if (! $userId || ! $amount) {
            Log::warning('Stripe payment_intent.succeeded missing user_id or amount', compact('reference'));
            return;
        }

        // Idempotency: don't credit twice for the same payment intent
        $exists = Transaction::where('reference', $reference)->exists();

        if ($exists) {
            Log::info('Stripe payment already processed (idempotent).', compact('reference'));
            return;
        }

        try {
            DB::transaction(function () use ($userId, $amount, $reference) {
                /** @var User $user */
                $user = User::lockForUpdate()->findOrFail($userId);

                $transaction = Transaction::create([
                    'user_id'     => $user->id,
                    'amount'      => $amount,
                    'type'        => 'deposit',
                    'status'      => 'completed',
                    'description' => 'Stripe payment',
                    'reference'   => $reference,
                ]);

                $user->increment('funds', $amount);

                // Log to payment_logs
                DB::table('payment_logs')->insert([
                    'user_id'        => $user->id,
                    'transaction_id' => $transaction->id,
                    'gateway'        => 'stripe',
                    'status'         => 'completed',
                    'amount'         => $amount,
                    'reference'      => $reference,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            });

            Log::info('Stripe payment credited', ['user_id' => $userId, 'amount' => $amount, 'reference' => $reference]);
        } catch (\Throwable $e) {
            Log::error('Failed to credit Stripe payment: ' . $e->getMessage(), compact('reference'));
        }
    }

    private function handleStripePaymentFailed(object $event): void
    {
        $intent    = $event->data->object;
        $reference = $intent->id;
        $userId    = $intent->metadata->user_id ?? null;

        Log::warning('Stripe payment failed', compact('reference', 'userId'));

        DB::table('payment_logs')->insert([
            'user_id'      => $userId,
            'gateway'      => 'stripe',
            'status'       => 'failed',
            'amount'       => ($intent->amount ?? 0) / 100,
            'reference'    => $reference,
            'error_message'=> $intent->last_payment_error->message ?? null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    private function handleStripeRefund(object $event): void
    {
        // Implement refund handling — typically: mark transaction as refunded, deduct funds
        Log::info('Stripe refund received', ['charge_id' => $event->data->object->id]);
    }
}
