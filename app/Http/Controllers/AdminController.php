<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentResource;
use App\Http\Resources\WebhookEventResource;
use App\Services\PaymentService;
use App\Services\WebhookService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    protected $paymentService;

    protected $webhookService;

    public function __construct(PaymentService $paymentService, WebhookService $webhookService)
    {
        $this->paymentService = $paymentService;
        $this->webhookService = $webhookService;
    }

    public function payments(Request $request)
    {
        $payments = $this->paymentService->list();

        return response()->json([
            'success' => true,
            'message' => 'Payments retrieved successfully.',
            'data' => PaymentResource::collection($payments->items()),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    public function webhookEvents(Request $request)
    {
        $events = $this->webhookService->list();

        return response()->json([
            'success' => true,
            'message' => 'Webhook events retrieved successfully.',
            'data' => WebhookEventResource::collection($events->items()),
            'meta' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage(),
            ],
        ]);
    }
}
