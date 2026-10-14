<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RiderAccountService
{
    public function signIn(string $email, ?array $identity = null): User
    {
        return DB::transaction(function () use ($email, $identity) {
            $email = mb_strtolower(trim($email));
            $user = User::query()->where('email', $email)->lockForUpdate()->first()
                ?? User::create(['email' => $email, 'name' => null, 'password' => null, 'is_active' => true]);
            if (! $user->is_active || $user->role !== null) {
                throw ValidationException::withMessages(['email' => 'Use the admin sign-in for staff accounts, or contact Rylie if your account is inactive.']);
            }
            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
            if ($identity) {
                $existing = DB::table('social_identities')->where('provider', $identity['provider'])->where('subject', $identity['subject'])->first();
                if ($existing && $existing->user_id !== $user->id) {
                    throw ValidationException::withMessages(['email' => 'This sign-in is already linked to another account.']);
                }
                DB::table('social_identities')->updateOrInsert(
                    ['provider' => $identity['provider'], 'subject' => $identity['subject']],
                    ['user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()],
                );
            }
            $this->customerFor($user);

            return $user;
        }, 3);
    }

    private function claimUnassignedRiders(Customer $customer, User $user): void
    {
        foreach (Rider::query()->whereNull('customer_id')->where('email', $user->email)
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
            ->whereNull('deleted_at')->whereNull('merged_into_id')->lockForUpdate()->get() as $rider) {
            $key = RiderIdentityService::key([...$rider->getAttributes(), 'customer_id' => $customer->id]);
            $existing = Rider::query()->where('identity_key', $key)->whereNull('deleted_at')->whereNull('merged_into_id')->first();
            $rider->update(['customer_id' => $customer->id, 'merged_into_id' => $existing?->id]);
        }
    }

    public function customerFor(User $user): Customer
    {
        abort_unless($user->is_active && $user->role === null && $user->email_verified_at !== null, 403);

        return DB::transaction(function () use ($user) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $customer = Customer::query()->where('user_id', $user->id)->whereNull('deleted_at')->first();
            if ($customer) {
                $this->claimUnassignedRiders($customer, $user);

                return $customer;
            }
            $matches = Customer::query()->where('email', $user->email)->whereNull('deleted_at')->lockForUpdate()->get();
            $customer = $matches->firstWhere('user_id', null);
            if ($matches->contains(fn ($c) => $c->user_id !== null && $c->user_id !== $user->id)) {
                throw ValidationException::withMessages(['email' => 'Contact Rylie to link your existing customer profile.']);
            }
            if ($customer) {
                $customer->update(['user_id' => $user->id]);

                $this->claimUnassignedRiders($customer, $user);

                return $customer;
            }

            $customer = Customer::create([
                'user_id' => $user->id, 'email' => $user->email,
                'first_name' => '', 'last_name' => '',
            ]);
            $this->claimUnassignedRiders($customer, $user);

            return $customer;
        });
    }
}
