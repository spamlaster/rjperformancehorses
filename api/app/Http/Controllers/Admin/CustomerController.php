<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends AdminController
{
    public function index(): JsonResponse
    {
        return response()->json(Customer::query()->orderBy('id')->get());
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(Customer::create($this->fields($request, partial: false))->refresh(), 201);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $customer->update($this->fields($request, partial: true));

        return response()->json($customer);
    }

    private function fields(Request $request, bool $partial): array
    {
        return $this->validateFields($request, [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ], $partial);
    }
}
