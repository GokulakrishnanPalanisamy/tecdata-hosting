<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebhookPaymentRequest;
use App\Services\WebhookService;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    protected $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    public function payment(WebhookPaymentRequest $request)
    {
        $result = $this->webhookService->receive($request->validated());

        if ($result['duplicate']) {
            return response()->json([
                'success' => true,
                'message' => 'Webhook already processed.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Webhook received successfully.',
        ], Response::HTTP_ACCEPTED);
    }
}
