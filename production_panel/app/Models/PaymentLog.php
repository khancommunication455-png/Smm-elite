<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable payment audit row used for admin inspection and reconciliation.
 * SECURITY: webhook processors write sanitized gateway payloads here so support
 * staff can audit payment decisions without exposing raw secrets or headers.
 */
class PaymentLog extends Model
{
    protected $fillable = [
        'user_id',
        'transaction_id',
        'gateway',
        'status',
        'amount',
        'reference',
        'response',
        'error_message',
    ];

    protected $casts = [
        'amount' => 'decimal:6',
        'response' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
