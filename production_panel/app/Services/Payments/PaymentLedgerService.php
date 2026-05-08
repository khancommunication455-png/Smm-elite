<?php

namespace App\Services\Payments;

use App\Models\PaymentLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Atomic payment ledger operations.
 * FINANCIAL SAFETY: all balance mutations happen inside database transactions
 * with row locks and reference-level idempotency to prevent double credits.
 */
class PaymentLedgerService
{
    public function creditDeposit(string $gateway, int $userId, float $amount, string $reference, array $auditPayload = []): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        DB::transaction(function () use ($gateway, $userId, $amount, $reference, $auditPayload): void {
            if (Transaction::where('reference', $reference)->where('type', 'deposit')->exists()) {
                Log::info('Payment deposit already credited; skipping idempotent replay.', [
                    'gateway' => $gateway,
                    'reference' => $reference,
                ]);
                return;
            }

            $user = User::query()->lockForUpdate()->findOrFail($userId);

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'deposit',
                'status' => 'completed',
                'description' => ucfirst($gateway).' verified payment',
                'reference' => $reference,
            ]);

            $user->increment('funds', $amount);

            PaymentLog::create([
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'gateway' => $gateway,
                'status' => 'completed',
                'amount' => $amount,
                'reference' => $reference,
                'response' => $this->sanitizeAuditPayload($auditPayload),
            ]);
        }, 3);
    }

    public function recordFailure(string $gateway, ?int $userId, float $amount, string $reference, string $message, array $auditPayload = []): void
    {
        PaymentLog::create([
            'user_id' => $userId,
            'gateway' => $gateway,
            'status' => 'failed',
            'amount' => max($amount, 0),
            'reference' => $reference,
            'error_message' => mb_substr($message, 0, 2000),
            'response' => $this->sanitizeAuditPayload($auditPayload),
        ]);
    }

    private function sanitizeAuditPayload(array $payload): array
    {
        unset(
            $payload['client_secret'],
            $payload['access_token'],
            $payload['api_key'],
            $payload['payer']['email_address']
        );

        return $payload;
    }
}
