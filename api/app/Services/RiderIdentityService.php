<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Rider;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RiderIdentityService
{
    public static function normalize(string $value): string
    {
        return mb_strtolower(preg_replace('/\\s+/u', ' ', trim($value)));
    }

    public static function key(array $fields): ?string
    {
        $owner = ! empty($fields['customer_id']) ? 'customer:'.$fields['customer_id']
            : (! empty($fields['email']) ? 'email:'.self::normalize($fields['email']) : null);

        return $owner === null ? null : hash('sha256', json_encode([
            $owner, self::normalize($fields['first_name']), self::normalize($fields['last_name']),
            isset($fields['date_of_birth']) ? substr((string) $fields['date_of_birth'], 0, 10) : '',
        ]));
    }

    /** A customer row lock serializes profile creation with guest booking. */
    public function findOrCreate(Customer $customer, array $input): Rider
    {
        return DB::transaction(function () use ($customer, $input) {
            Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            if (! empty($input['id'])) {
                return Rider::query()->whereKey($input['id'])->where('customer_id', $customer->id)
                    ->whereNull('deleted_at')->whereNull('merged_into_id')->firstOrFail();
            }

            $first = trim(preg_replace('/\\s+/u', ' ', $input['first_name']));
            $last = trim(preg_replace('/\\s+/u', ' ', $input['last_name']));
            $birthday = $input['date_of_birth'] ?? null;
            $matches = Rider::query()->where('customer_id', $customer->id)->whereNull('deleted_at')->whereNull('merged_into_id')
                ->get()->filter(fn ($r) => self::normalize($r->first_name) === self::normalize($first)
                    && self::normalize($r->last_name) === self::normalize($last));
            $exact = $matches->first(fn ($r) => $r->date_of_birth?->toDateString() === $birthday);
            if ($exact) {
                return $exact;
            }
            if ($matches->count() === 1) {
                $existing = $matches->first();
                if ($birthday === null || $existing->date_of_birth === null) {
                    if ($birthday !== null) {
                        $existing->update(['date_of_birth' => $birthday]);
                    }

                    return $existing;
                }
            }
            if ($birthday === null && $matches->isNotEmpty()) {
                throw ValidationException::withMessages(['riders' => 'Choose a saved rider or enter their date of birth to identify the correct person.']);
            }

            return Rider::create([
                'customer_id' => $customer->id, 'first_name' => $first, 'last_name' => $last,
                'date_of_birth' => $birthday, 'riding_experience' => 'beginner',
            ]);
        });
    }
}
