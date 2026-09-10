<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService) {
        $this->orderService = $orderService;
    }

    public function index(Request $request)
    {
        $orders = $this->orderService->listOrder($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully.',
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->createOrder($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully.',
            'data' => new OrderResource($order),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, int $id)
    {
        $order = $this->orderService->findOrder($request->user(), $id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully.',
            'data' => new OrderResource($order),
        ]);
    }
}
