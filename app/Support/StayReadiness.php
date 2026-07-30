<?php

namespace App\Support;

use App\Models\Booking;

/**
 * Computes whether a booking is ready to be checked in, following the same
 * compliance rules the front desk enforces (responsible adult with a full
 * address, verified ID for every adult, passport + visa for foreign nationals).
 *
 * Shared by the check-in workspace and the dashboard so both agree on "ready".
 * Requires the booking's `guests.documents` relation to be loaded.
 */
class StayReadiness
{
    /**
     * @return array{ready: bool, blockers: list<string>}
     */
    public static function for(Booking $booking): array
    {
        $blockers = [];
        $adults = $booking->guests->where('is_staying', true)->filter->isAdult();

        if ($adults->isEmpty()) {
            $blockers[] = 'Add at least one adult occupant.';
        }

        if (! $booking->guests->contains(fn ($guest) => $guest->is_staying && $guest->role === 'primary' && $guest->isAdult())) {
            $blockers[] = 'Select a responsible primary adult.';
        }

        $primary = $booking->guests->first(fn ($guest) => $guest->is_staying && $guest->role === 'primary' && $guest->isAdult());
        if ($primary && collect(['address_line_1', 'city', 'state', 'postal_code', 'country'])->contains(fn ($field) => blank($primary->{$field}))) {
            $blockers[] = "Complete address for {$primary->full_name}.";
        }

        foreach ($adults as $guest) {
            if (! $guest->documents->contains('verification_status', 'verified')) {
                $blockers[] = "Verify ID for {$guest->full_name}.";
            }
            if ($guest->isForeignNational()) {
                if (! $guest->documents->where('verification_status', 'verified')->contains('document_type', 'passport')) {
                    $blockers[] = "Verify passport for {$guest->full_name}.";
                }
                if (! $guest->documents->where('verification_status', 'verified')->contains('document_type', 'visa')) {
                    $blockers[] = "Verify visa/OCI document for {$guest->full_name}.";
                }
            }
        }

        $blockers = array_values(array_unique($blockers));

        return ['ready' => $blockers === [], 'blockers' => $blockers];
    }
}
