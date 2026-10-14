<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BusinessConfig;
use App\Models\Rider;
use App\Models\Waiver;
use App\Models\WaiverAcceptance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class WaiverService
{
    public static function rules(): array
    {
        return [
            'waiver.id' => ['required', 'string', 'max:36'],
            'waiver.document_hash' => ['required', 'string', 'size:64'],
            'waiver.signatures' => ['required', 'array', 'min:1'],
            'waiver.signatures.*.signer_name' => ['required', 'string', 'min:2', 'max:255'],
            'waiver.signatures.*.relationship_to_rider' => ['required', 'in:self,parent,legal_guardian'],
            'waiver.signatures.*.accepted' => ['required', 'accepted'],
            'waiver.signatures.*.authorized' => ['required', 'accepted'],
        ];
    }

    public static function current(bool $lock = false): ?Waiver
    {
        return Waiver::query()->where('active', true)
            ->where('effective_date', '<=', CarbonImmutable::now(BusinessConfig::timezone())->toDateString())
            ->orderByDesc('version')->when($lock, fn ($q) => $q->lockForUpdate())->first();
    }

    public static function fingerprint(Waiver $waiver): string
    {
        return hash('sha256', json_encode([
            $waiver->id, $waiver->title, $waiver->version, $waiver->content, $waiver->effective_date->toDateString(),
        ]));
    }

    public static function document(Waiver $waiver): array
    {
        return [
            'id' => $waiver->id, 'title' => $waiver->title, 'version' => $waiver->version,
            'content' => $waiver->content, 'effective_date' => $waiver->effective_date->toDateString(),
            'document_hash' => self::fingerprint($waiver),
            'current_date' => CarbonImmutable::now(BusinessConfig::timezone())->toDateString(),
        ];
    }

    public function validateSubmission(array $data): Waiver
    {
        Validator::make($data, self::rules())->validate();
        $waiver = self::current(lock: true);
        if (! $waiver || $waiver->id !== $data['waiver']['id']
            || ! hash_equals(self::fingerprint($waiver), $data['waiver']['document_hash'])) {
            throw ValidationException::withMessages(['waiver' => 'The waiver has changed or is unavailable. Refresh the waiver and sign the current version before booking.']);
        }

        return $waiver;
    }

    public function record(Waiver $waiver, Booking $booking, array $riderIds, array $signatures, array $metadata): void
    {
        if (count($signatures) !== count($riderIds)) {
            throw ValidationException::withMessages(['waiver.signatures' => 'A signed waiver is required for every rider in this booking.']);
        }
        $riders = Rider::query()->whereIn('id', $riderIds)->get()->keyBy('id');
        $adultBirthday = CarbonImmutable::now(BusinessConfig::timezone())->subYears(18)->toDateString();
        foreach (array_values($riderIds) as $index => $riderId) {
            $signature = $signatures[$index];
            $rider = $riders[$riderId];
            if ($signature['relationship_to_rider'] === 'self' && $rider->date_of_birth
                && $rider->date_of_birth->toDateString() > $adultBirthday) {
                throw ValidationException::withMessages(["waiver.signatures.{$index}.relationship_to_rider" => 'A parent or legal guardian must sign for riders under 18.']);
            }
            WaiverAcceptance::create([
                'waiver_id' => $waiver->id, 'customer_id' => $booking->customer_id,
                'rider_id' => $riderId, 'booking_id' => $booking->id,
                'signer_name' => trim($signature['signer_name']),
                'signer_email' => $booking->customer->email,
                'relationship_to_rider' => $signature['relationship_to_rider'],
                'accepted_at' => CarbonImmutable::now('UTC'),
                'signature_data' => json_encode(['type' => 'typed', 'name' => trim($signature['signer_name']), 'accepted' => true, 'authorized' => true]),
                'waiver_title' => $waiver->title, 'waiver_version' => $waiver->version, 'waiver_content' => $waiver->content,
                'document_hash' => self::fingerprint($waiver),
                'ip_address' => $metadata['ip_address'] ?? null,
                'user_agent' => isset($metadata['user_agent']) ? mb_substr($metadata['user_agent'], 0, 2000) : null,
            ]);
        }
    }
}
