<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booking;
use App\Models\RecurringLesson;
use App\Models\WaiverAcceptance;
use Illuminate\Http\JsonResponse;

class WaiverController extends AdminController
{
    public function booking(Booking $booking): JsonResponse
    {
        abort_if($booking->deleted_at !== null, 404);

        return $this->records([$booking->id]);
    }

    public function recurring(RecurringLesson $recurringLesson): JsonResponse
    {
        abort_if($recurringLesson->deleted_at !== null, 404);

        return $this->records(Booking::query()->where('recurring_lesson_id', $recurringLesson->id)->pluck('id')->all());
    }

    private function records(array $bookingIds): JsonResponse
    {
        return response()->json(WaiverAcceptance::query()->whereIn('booking_id', $bookingIds)
            ->with('rider:id,first_name,last_name')->orderBy('accepted_at')->get()
            ->map(fn ($record) => [
                'id' => $record->id, 'booking_id' => $record->booking_id,
                'rider_name' => $record->rider ? "{$record->rider->first_name} {$record->rider->last_name}" : 'Rider',
                'signer_name' => $record->signer_name, 'signer_email' => $record->signer_email,
                'relationship_to_rider' => $record->relationship_to_rider, 'accepted_at' => $record->accepted_at->format('Y-m-d H:i:s').' UTC',
                'waiver_title' => $record->waiver_title, 'waiver_version' => $record->waiver_version,
                'waiver_content' => $record->waiver_content, 'document_hash' => $record->document_hash,
            ]))->header('Cache-Control', 'no-store');
    }
}
