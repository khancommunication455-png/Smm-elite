<?php

namespace App\Services\Payments;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Centralizes gateway webhook verification.
 * SECURITY: controllers must call this before persisting or queueing payment
 * events so unsigned/forged payloads never enter the trusted processing path.
 */
class PaymentWebhookVerifier
{
    public function verifyStripe(Request $request): object
    {
        $secret = config('services.stripe.webhook_secret');

        if (! $secret) {
            throw new RuntimeException('STRIPE_WEBHOOK_SECRET is not configured.');
        }

        try {
            return Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                $secret,
                (int) config('services.stripe.webhook_tolerance', 300)
            );
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed.', [
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function verifyPayPal(Request $request): array
    {
        $webhookId = config('services.paypal.webhook_id');
        $clientId = config('services.paypal.client_id');
        $clientSecret = config('services.paypal.client_secret');

        if (! $webhookId || ! $clientId || ! $clientSecret) {
            throw new RuntimeException('PayPal webhook verification credentials are not configured.');
        }

        $payload = $request->json()->all();
        $verificationPayload = [
            'auth_algo' => $request->header('PAYPAL-AUTH-ALGO'),
            'cert_url' => $request->header('PAYPAL-CERT-URL'),
            'transmission_id' => $request->header('PAYPAL-TRANSMISSION-ID'),
            'transmission_sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
            'webhook_id' => $webhookId,
            'webhook_event' => $payload,
        ];

        foreach (['auth_algo', 'cert_url', 'transmission_id', 'transmission_sig', 'transmission_time'] as $field) {
            if (empty($verificationPayload[$field])) {
                throw new RuntimeException("Missing PayPal webhook header: {$field}");
            }
        }

        $baseUrl = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $token = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->timeout(10)
            ->retry(2, 200)
            ->post($baseUrl.'/v1/oauth2/token', ['grant_type' => 'client_credentials'])
            ->throw()
            ->json('access_token');

        $status = Http::withToken($token)
            ->timeout(10)
            ->retry(2, 200)
            ->post($baseUrl.'/v1/notifications/verify-webhook-signature', $verificationPayload)
            ->throw()
            ->json('verification_status');

        if ($status !== 'SUCCESS') {
            Log::warning('PayPal webhook signature verification failed.', [
                'ip' => $request->ip(),
                'transmission_id' => $verificationPayload['transmission_id'],
                'status' => $status,
            ]);

            throw new RuntimeException('Invalid PayPal webhook signature.');
        }

        return $payload;
    }
}
