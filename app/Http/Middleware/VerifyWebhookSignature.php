<?php

namespace App\Http\Middleware;

use App\Services\WebhookService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function __construct(protected WebhookService $webhookService) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->webhookService->hasValidSignature($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
