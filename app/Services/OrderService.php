<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Get a paginated list of orders belonging to the given user.
     */
    public function listOrder(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a single order belonging to the given user.
     */
    public function findOrder(User $user, int $orderId): ?Order
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->where('id', $orderId)
            ->first();
    }

    /**
     * Create a new order for the given users.
     */
    public function createOrder(User $user, array $data): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'order_number' => $this->generateUniqueOrderNumber(),
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'amount' => $data['amount'],
            'status' => OrderStatus::PENDING,
        ]);
    }

    /**
     * Generate a unique, human-readable order number, e.g. ORD-20260907-AB12CD.
     */
    protected function generateUniqueOrderNumber(): string
    {
        do {
            $candidate = sprintf(
                'ORD-%s-%s',
                now()->format('Ymd'),
                Str::upper(Str::random(6))
            );
        } while (Order::where('order_number', $candidate)->exists());

        return $candidate;
    }
}
