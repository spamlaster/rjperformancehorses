<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use App\Models\Rider;
use App\Services\RiderIdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiderController extends AdminController
{
    public function index(): JsonResponse
    {
        return response()->json(Rider::query()->whereNull('deleted_at')->whereNull('merged_into_id')->orderBy('id')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $input = $this->fields($request, partial: false);
        $customer = ! empty($input['customer_id']) ? Customer::find($input['customer_id'])
            : (! empty($input['email']) ? Customer::where('email', mb_strtolower(trim($input['email'])))->whereNull('deleted_at')->first() : null);
        if ($customer) {
            return response()->json(app(RiderIdentityService::class)->findOrCreate($customer, $input)->refresh(), 201);
        }

        return response()->json(Rider::create($input)->refresh(), 201);
    }

    public function update(Request $request, Rider $rider): JsonResponse
    {
        $rider->update($this->fields($request, partial: true));

        return response()->json($rider);
    }

    /** Hard delete, as before; refused (409) while bookings reference the rider. */
    public function destroy(Rider $rider): JsonResponse
    {
        $rider->delete();

        return response()->json(['success' => true]);
    }

    private function fields(Request $request, bool $partial): array
    {
        return $this->validateFields($request, [
            'customer_id' => ['nullable', 'string', 'exists:customers,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date_format:Y-m-d'],
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'riding_experience' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ], $partial);
    }
}
