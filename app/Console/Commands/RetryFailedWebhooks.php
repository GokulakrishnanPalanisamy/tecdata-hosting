<?php

namespace App\Console\Commands;

use App\Enums\WebhookEventStatus;
use App\Jobs\ProcessWebhookEvent;
use App\Models\WebhookEvent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('webhooks:retry-failed')]
#[Description('Find failed webhook events and dispatch them for reprocessing.')]
class RetryFailedWebhooks extends Command
{
    /**
     * Total re-try counts
     */
    protected const MAX_RETRY_COUNT_FOR_RECOVERY = 3;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $eligibleIds = WebhookEvent::query()
            ->where('status', WebhookEventStatus::FAILED)
            ->where('retry_count', '<', self::MAX_RETRY_COUNT_FOR_RECOVERY)
            ->pluck('id');

        $dispatched = 0;

        foreach ($eligibleIds as $id) {
            $claimed = WebhookEvent::query()
                ->where('id', $id)
                ->where('status', WebhookEventStatus::FAILED)
                ->update(['status' => WebhookEventStatus::PROCESSING]);

            if (! $claimed) {
                continue;
            }

            ProcessWebhookEvent::dispatch($id);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} failed webhook event(s) for reprocessing.");
    }
}
