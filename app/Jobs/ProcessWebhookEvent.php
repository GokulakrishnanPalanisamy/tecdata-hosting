<?php

namespace App\Jobs;

use App\Enums\WebhookEventStatus;
use App\Models\WebhookEvent;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessWebhookEvent implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times the queue worker will attempt this job before giving up.
     */
    public int $tries = 3;

    public function __construct(public int $webhookEventId) {}

    /**
     * Seconds to wait before each retry attempt.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookService $webhookService): void
    {
        $event = WebhookEvent::find($this->webhookEventId);

        if (! $event) {
            return;
        }

        if ($event->status === WebhookEventStatus::PROCESSED) {
            // Already processed by an earlier attempt/worker - do not reprocess.
            return;
        }

        try {

            $webhookService->processEvent($event);

        } catch (Throwable $e) {

            $event->update(['error_message' => $e->getMessage()]);
            $event->increment('retry_count');

            throw $e;
        }
    }

    /**
     * Handle a job failure after all retry attempts have been exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        $event = WebhookEvent::find($this->webhookEventId);

        if (! $event) {
            return;
        }

        $event->update([
            'status' => WebhookEventStatus::FAILED,
            'error_message' => $exception?->getMessage() ?? $event->error_message,
        ]);
    }
}
