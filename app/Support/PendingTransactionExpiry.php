<?php

namespace App\Support;

use App\Enums\TransactionStatus;
use App\Jobs\SendTransactionCallback;
use App\Models\Transaction;

/**
 * Ends a mobile-money prompt that was not approved promptly.
 *
 * The cutoff is applied by the scheduler and again in the verification path,
 * so an old pending transaction cannot be made successful by a later check.
 */
final class PendingTransactionExpiry
{
    public const TIMEOUT_SECONDS = 120;

    public static function expireOverdue(): int
    {
        $transactions = Transaction::query()
            ->where('status', TransactionStatus::PENDING)
            ->where('created_at', '<=', now()->subSeconds(self::TIMEOUT_SECONDS))
            ->get();

        foreach ($transactions as $transaction) {
            self::expire($transaction);
        }

        return $transactions->count();
    }

    public static function expire(Transaction $transaction): bool
    {
        if ($transaction->status !== TransactionStatus::PENDING
            || ! $transaction->created_at
            || $transaction->created_at->gt(now()->subSeconds(self::TIMEOUT_SECONDS))) {
            return false;
        }

        $payload = is_array($transaction->provider_response)
            ? $transaction->provider_response
            : [];
        $payload['switch'] = array_merge(
            is_array($payload['switch'] ?? null) ? $payload['switch'] : [],
            [
                'status' => 'expired',
                'reason' => 'Payment prompt was not approved within two minutes',
                'expired_at' => now()->toIso8601String(),
            ],
        );

        $transaction->forceFill([
            'status' => TransactionStatus::FAILED,
            'provider_response' => $payload,
        ])->save();

        if ($transaction->callback_url && ! $transaction->callback_notified_at) {
            SendTransactionCallback::dispatch($transaction->fresh(['paymentProvider']));
        }

        return true;
    }
}
