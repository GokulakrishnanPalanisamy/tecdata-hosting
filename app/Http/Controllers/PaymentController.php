<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentResource;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    protected $orderService;

    protected $paymentService;

    public function __construct(OrderService $orderService, PaymentService $paymentService)
    {
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
    }

    public function initiatePayment(Request $request, int $id)
    {
        $order = $this->orderService->findOrder($request->user(), $id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (! $this->paymentService->isPayable($order)) {
            return response()->json([
                'success' => false,
                'message' => 'This order is all-ready in progressing for payment.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payment = $this->paymentService->initiate($order);

        return response()->json([
            'success' => true,
            'message' => 'Payment initiated successfully.',
            'data' => new PaymentResource($payment),
        ], Response::HTTP_CREATED);
    }
}
