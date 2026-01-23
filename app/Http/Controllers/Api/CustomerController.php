<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterCustomerRequest;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService
    ) {
    }

    /**
     * Register a new customer.
     */
    public function store(RegisterCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->register($request->validated());

        return response()->json([
            'message' => 'Customer registered successfully',
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'created_at' => $customer->created_at,
            ],
        ], 201);
    }
}
