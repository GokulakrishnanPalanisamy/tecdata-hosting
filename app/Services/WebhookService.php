<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\WebhookEventStatus;
use App\Jobs\ProcessWebhookEvent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\WebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WebhookService
{
    /**
     * Get a paginated list of all webhook events, most recent first.
     */
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return WebhookEvent::query()->latest()->paginate($perPage);
    }

    /**
     * Verify that the request carries a valid HMAC-SHA256 signature,
     * generated from the raw request body using the configured webhook secret.
     */
    public function hasValidSignature(Request $request): bool
    {
        $signature = $request->header('X-WEBHOOK-SIGNATURE');
        $secret = config('services.webhook.secret');

        if (! $signature || ! $secret) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Receive an already signature-verified, structurally valid webhook payload:
     * guarantee the event is stored at most once, then dispatch it for
     * asynchronous processing. Never performs payment/order processing itself.
     *
     * @param  array{event_id: string, event_type: string, transaction_id: string, order_id: int, amount: float|string, timestamp: string}  $payload
     * @return array{event: WebhookEvent, duplicate: bool}
     */
    public function receive(array $payload): array
    {
        $existing = WebhookEvent::where('event_id', $payload['event_id'])->first();

        if ($existing) {
            return ['event' => $existing, 'duplicate' => true];
        }

        [$event, $wasCreated] = $this->createEvent($payload);

        if (! $wasCreated) {
            // Lost the race: another request committed this event_id between
            // our pre-check above and our insert attempt.
            return ['event' => $event, 'duplicate' => true];
        }

        ProcessWebhookEvent::dispatch($event->id);

        return ['event' => $event, 'duplicate' => false];
    }

    /**
     * Attempt to create the webhook_events record for this event_id.
     *
     * The pre-check in receive() is only a cheap optimisation for the common
     * case; the unique constraint on event_id is the real, final guarantee
     * against two near-simultaneous requests for the same event both
     * proceeding to processing. If the insert loses that race, the unique
     * constraint violation is caught here and treated as a duplicate.
     *
     * @param  array{event_id: string, event_type: string, ...}  $payload
     * @return array{0: WebhookEvent, 1: bool} the event, and whether *this* call created it
     */
    protected function createEvent(array $payload): array
    {
        try {
            $event = WebhookEvent::create([
                'event_id' => $payload['event_id'],
                'event_type' => $payload['event_type'],
                'payload' => $payload,
                'status' => WebhookEventStatus::RECEIVED,
                'retry_count' => 0,
            ]);

            return [$event, true];
        } catch (QueryException $e) {
            if (! $this->isUniqueConstraintViolation($e)) {
                throw $e;
            }

            return [WebhookEvent::where('event_id', $payload['event_id'])->firstOrFail(), false];
        }
    }

    public function processEvent(WebhookEvent $event): void
    {
        $event->update(['status' => WebhookEventStatus::PROCESSING]);

        DB::transaction(function () use ($event) {
            $this->applyBusinessLogic($event);
        });

        $event->update([
            'status' => WebhookEventStatus::PROCESSED,
            'processed_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Apply the business effect of a single webhook event to the related
     * payment and order.
     */
    protected function applyBusinessLogic(WebhookEvent $event): void
    {
        $payload = $event->payload;

        $order = Order::find($payload['order_id']);

        if (! $order) {
            throw new \RuntimeException("Order [{$payload['order_id']}] not found.");
        }

        $payment = Payment::where('order_id', $payload['order_id'])->first();

        if (! $payment) {
            // Initiate Payment is first triggered before processing the webhook.
            throw new \RuntimeException("Payment for [{$order->id}] does not exist.");
        }

        match ($event->event_type) {
            'payment.success' => $this->markPaymentSuccessful($payment, $order),
            'payment.failed' => $this->markPaymentFailed($payment, $order),
            default => throw new \RuntimeException("Unsupported event type [{$event->event_type}]."),
        };
    }

    protected function markPaymentSuccessful(Payment $payment, Order $order): void
    {
        $payment->update(['status' => PaymentStatus::SUCCESS]);
        $order->update(['status' => OrderStatus::PAID]);
    }

    protected function markPaymentFailed(Payment $payment, Order $order): void
    {
        $payment->update(['status' => PaymentStatus::FAILED]);
        $order->update(['status' => OrderStatus::FAILED]);
    }

    /**
     * Determine whether a query exception was caused by a unique constraint violation.
     */
    protected function isUniqueConstraintViolation(QueryException $e): bool
    {
        return $e->getCode() === '23000';
    }
}
