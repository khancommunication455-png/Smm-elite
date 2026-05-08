<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Durable idempotency ledger for payment webhooks.
 * RISK MITIGATION: gateways retry webhooks and attackers may replay captured
 * payloads; this table guarantees each verified gateway event is processed once.
 */
class PaymentWebhookEvent extends Model
{
    protected $fillable = [
        'gateway',
        'gateway_event_id',
        'event_type',
        'status',
        'payload',
        'attempts',
        'processed_at',
        'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function markProcessing(): void
    {
        $this->forceFill([
            'status' => 'processing',
            'attempts' => $this->attempts + 1,
            'last_error' => null,
        ])->save();
    }

    public function markProcessed(): void
    {
        $this->forceFill([
            'status' => 'processed',
            'processed_at' => now(),
            'last_error' => null,
        ])->save();
    }

    public function markFailed(string $message): void
    {
        $this->forceFill([
            'status' => 'failed',
            'last_error' => mb_substr($message, 0, 2000),
        ])->save();
    }
}
