<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Banquet;
use App\Models\BanquetLead;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BanquetController extends Controller
{
    public function enquiry(Request $request, Banquet $banquet): RedirectResponse
    {
        abort_unless($banquet->status === Banquet::STATUS_ACTIVE, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:60'],
            'event_date' => ['nullable', 'string', 'max:60'],
            'guest_count' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $lead = BanquetLead::create($validated + [
            'banquet_id' => $banquet->id,
            'property_id' => $banquet->property_id,
            'status' => BanquetLead::STATUS_NEW,
        ]);

        $this->notifyEventsTeam($banquet, $lead);

        return redirect()
            ->route('banquet.show', $banquet)
            ->with('lead_sent', "Thanks {$lead->name}! Our events team will call you shortly.");
    }

    private function tryFormat(?string $value, string $format): ?string
    {
        if (blank($value)) {
            return null;
        }
        try {
            return \Illuminate\Support\Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return $value;
        }
    }

    private function notifyEventsTeam(Banquet $banquet, BanquetLead $lead): void
    {
        $settings = \Illuminate\Support\Facades\Schema::hasTable('settings') ? \App\Models\Setting::first() : null;
        $to = $settings?->reservations_email ?: $settings?->primary_email;
        if (blank($to)) {
            return;
        }

        try {
            \Illuminate\Support\Facades\Mail::raw(
                "New banquet enquiry for {$banquet->name} ({$banquet->property?->name}).\n\n"
                    ."Name: {$lead->name}\nPhone: {$lead->phone}\nEmail: ".($lead->email ?: '—')."\n"
                    ."Event: ".($lead->event_type ?: '—')." on ".($lead->event_date ?: '—')."\n"
                    ."Guests: ".($lead->guest_count ?: '—')."\n\nMessage: ".($lead->message ?: '—'),
                fn ($message) => $message->to($to)->subject('Banquet enquiry — '.$banquet->name)
            );
        } catch (\Throwable) {
            // Lead is saved regardless; email delivery is best-effort.
        }
    }

    public function show(Request $request, Banquet $banquet): View
    {
        abort_unless(
            $banquet->status === Banquet::STATUS_ACTIVE
                && $banquet->property
                && $banquet->property->status === \App\Models\Property::STATUS_ACTIVE,
            404
        );

        $banquet->load(['property', 'images', 'prices', 'amenitiesList']);

        $guestCount = (int) $request->query('guest_count');

        return view('public.banquet.show', [
            'banquet' => $banquet,
            'eventContext' => [
                'guest_count' => $guestCount > 0 ? $guestCount : null,
                'event_type' => $request->query('event_type'),
                'event_date' => $this->tryFormat($request->query('event_date'), 'd M Y'),
                'event_time' => $this->tryFormat($request->query('event_time'), 'g:i A'),
            ],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function eventTypeLabels(): array
    {
        return [
            'marriage' => 'Marriage',
            'anniversary' => 'Anniversary',
            'birthday' => 'Birthday',
            'corporate_party' => 'Corporate Party',
            'meeting' => 'Meeting',
            'others' => 'Other event',
        ];
    }
}
