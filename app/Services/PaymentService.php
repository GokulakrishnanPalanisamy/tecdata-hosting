<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    /**
     * Get a paginated list of all payments, most recent first.
     */
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return Payment::query()->latest()->paginate($perPage);
    }

    /**
     * Determine whether the given order is currently eligible to have a payment initiated for it.
     */
    public function isPayable(Order $order): bool
    {
        if ($order->status !== OrderStatus::PENDING) {
            return false;
        }

        return ! $order->payments()->where('status', PaymentStatus::INITIATED)->exists();
    }

    public function initiate(Order $order): Payment
    {
        return DB::transaction(function () use ($order) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'transaction_id' => $this->generateUniqueTransactionId(),
                'amount' => $order->amount,
                'status' => PaymentStatus::INITIATED,
                'gateway_response' => [
                    'payment_method' => 'razorpay', // dummy data
                ],
            ]);

            $order->update([
                'status' => OrderStatus::PAYMENT_PROCESSING,
            ]);

            return $payment;
        });
    }

    /**
     * Generate a unique, human-readable transaction id, e.g. TXN-20260907-AB12CD.
     */
    protected function generateUniqueTransactionId(): string
    {
        do {
            $candidate = sprintf(
                'TXN-%s-%s',
                now()->format('Ymd'),
                Str::upper(Str::random(6))
            );
        } while (Payment::where('transaction_id', $candidate)->exists());

        return $candidate;
    }
}
