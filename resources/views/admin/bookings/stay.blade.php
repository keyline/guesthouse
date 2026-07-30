@extends('admin.layouts.app')

@section('title', 'Stay · '.$booking->booking_number)
@section('eyebrow', 'Front Desk Operations')
@section('page-title', $booking->status === 'checked_in' ? 'Manage Stay' : 'Prepare Check-in')

@section('header-actions')
    <a href="{{ route('admin.bookings.show', $booking) }}">Back to booking</a>
@endsection

@section('content')
    @if (session('status'))<div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-800">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800"><ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="grid gap-4 xl:grid-cols-[1fr_360px]">
        <div class="space-y-4">
            <section class="admin-card p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><p class="text-[10px] font-black uppercase tracking-widest text-slate-500">{{ $booking->booking_number }} · {{ $booking->property->name }}</p><h2 class="mt-1 text-lg font-black">{{ $booking->guest_name }}</h2><p class="text-xs font-semibold text-slate-500">{{ $booking->check_in_date->format('d M') }} → {{ $booking->check_out_date->format('d M Y') }} · {{ $booking->roomType->name }}</p></div>
                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $readiness['ready'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ $readiness['ready'] ? '✓ Ready for check-in' : count($readiness['blockers']).' action(s) required' }}</span>
                </div>
            </section>

            <section class="admin-card overflow-hidden">
                <div class="admin-card-header"><div><h2 class="text-sm font-black">Staying guests</h2><p class="text-xs text-slate-500">Verify every adult occupant. Foreign nationals require passport and visa/OCI records.</p></div></div>
                <div class="divide-y divide-slate-100">
                    @foreach ($booking->guests as $guest)
                        <article class="p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div><h3 class="font-black">{{ $guest->full_name }} @if($guest->role === 'primary')<span class="ml-1 rounded bg-blue-50 px-1.5 py-0.5 text-[9px] uppercase text-blue-700">Primary</span>@endif</h3><p class="mt-1 text-xs font-semibold text-slate-500">{{ ucfirst($guest->guest_type) }} · {{ $guest->nationality }} · {{ $guest->phone ?: 'No phone' }}</p></div>
                                <div class="flex items-center gap-2"><span class="rounded-full px-2 py-1 text-[10px] font-black {{ $guest->id_verification_status === 'verified' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ $guest->id_verification_status === 'verified' ? 'ID verified' : 'ID pending' }}</span>@if($guest->role !== 'primary' && $booking->status !== 'checked_in')<form method="POST" action="{{ route('admin.bookings.guests.destroy', [$booking,$guest]) }}">@csrf @method('DELETE')<button class="text-xs font-bold text-rose-600">Remove</button></form>@endif</div>
                            </div>
                            <details class="mt-2 rounded-md border border-slate-200 bg-white">
                                <summary class="flex cursor-pointer list-none items-center justify-between px-3 py-2 text-[11px] font-black text-slate-600"><span>Contact & address</span><span class="text-slate-400">{{ $guest->city ?: 'Address incomplete' }} · Edit</span></summary>
                                <form method="POST" action="{{ route('admin.bookings.guests.update', [$booking,$guest]) }}" class="grid gap-2 border-t border-slate-100 bg-slate-50/60 p-3 sm:grid-cols-2 lg:grid-cols-4">@csrf @method('PATCH')
                                    <label class="text-[10px] font-bold text-slate-500">Legal name<input name="full_name" value="{{ $guest->full_name }}" required class="mt-1 h-8 w-full rounded border border-slate-300 px-2 text-xs"></label>
                                    <label class="text-[10px] font-bold text-slate-500">Mobile<input name="phone" value="{{ $guest->phone }}" class="mt-1 h-8 w-full rounded border border-slate-300 px-2 text-xs"></label>
                                    <label class="text-[10px] font-bold text-slate-500">Email<input name="email" type="email" value="{{ $guest->email }}" class="mt-1 h-8 w-full rounded border border-slate-300 px-2 text-xs"></label>
                                    <label class="text-[10px] font-bold text-slate-500">Date of birth<input name="date_of_birth" type="date" value="{{ $guest->date_of_birth?->toDateString() }}" class="mt-1 h-8 w-full rounded border border-slate-300 px-2 text-xs"></label>
                                    <label class="text-[10px] font-bold text-slate-500">Nationality<input name="nationality" value="{{ $guest->nationality }}" required class="mt-1 h-8 w-full rounded border border-slate-300 px-2 text-xs"></label>
                                    <label class="text-[10px] font-bold text-slate-500 lg:col-span-2">Address<input name="address_line_1" value="{{ $guest->address_line_1 }}" class="mt-1 h-8 w-full rounded border border-slate-300 px-2 text-xs"></label>
                                    <label class="text-[10px] font-bold text-slate-500">City<input name="city" value="{{ $guest->city }}" class="mt-1 h-8 w-full rounded border border-slate-300 px-2 text-xs"></label>
                                    <label class="text-[10px] font-bold text-slate-500">State<input name="state" value="{{ $guest->state }}" class="mt-1 h-8 w-full rounded border border-slate-300 px-2 text-xs"></label>
                                    <label class="text-[10px] font-bold text-slate-500">Postal code<input name="postal_code" value="{{ $guest->postal_code }}" class="mt-1 h-8 w-full rounded border border-slate-300 px-2 text-xs"></label>
                                    <label class="text-[10px] font-bold text-slate-500">Country<input name="country" value="{{ $guest->country ?: 'India' }}" required class="mt-1 h-8 w-full rounded border border-slate-300 px-2 text-xs"></label>
                                    <div class="flex items-end"><button class="h-8 rounded bg-slate-900 px-3 text-[11px] font-black text-white">Save details</button></div>
                                </form>
                            </details>
                            @if ($guest->documents->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-2">@foreach($guest->documents as $document)<span class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-[10px] font-bold text-slate-600">{{ ucfirst(str_replace('_',' ',$document->document_type)) }} · {{ $document->document_number_masked ?: 'number not stored' }} · <b class="{{ $document->verification_status === 'verified' ? 'text-emerald-700' : 'text-amber-700' }}">{{ ucfirst($document->verification_status) }}</b></span>@endforeach</div>
                            @endif
                            @if ($booking->status !== 'checked_out')
                                <details class="mt-3 rounded-lg border border-dashed border-slate-300 bg-slate-50/70 p-3"><summary class="cursor-pointer text-xs font-black text-slate-700">+ Add and verify ID document</summary>
                                    <form method="POST" action="{{ route('admin.bookings.guests.documents.store', [$booking,$guest]) }}" enctype="multipart/form-data" class="mt-3 grid gap-2 md:grid-cols-3">@csrf
                                        <select name="document_type" required class="h-9 rounded border border-slate-300 bg-white px-2 text-xs"><option value="">Document type</option><option value="passport">Passport</option><option value="visa">Visa / OCI</option><option value="driving_licence">Driving licence</option><option value="voter_id">Voter ID</option><option value="aadhaar_offline">Aadhaar offline / masked</option><option value="other">Other government ID</option></select>
                                        <input name="document_number" placeholder="Document number" class="h-9 rounded border border-slate-300 px-2 text-xs"><input name="issuing_country" placeholder="Issuing country" class="h-9 rounded border border-slate-300 px-2 text-xs">
                                        <input name="expires_at" type="date" class="h-9 rounded border border-slate-300 px-2 text-xs"><input name="front" type="file" accept="image/jpeg,image/png,application/pdf" required class="h-9 rounded border border-slate-300 bg-white p-1 text-xs"><input name="back" type="file" accept="image/jpeg,image/png,application/pdf" class="h-9 rounded border border-slate-300 bg-white p-1 text-xs">
                                        <label class="flex items-center gap-2 text-xs font-bold text-slate-700"><input type="checkbox" name="verified" value="1"> Original/digital document verified</label><button class="h-9 rounded bg-slate-900 px-3 text-xs font-black text-white">Store document</button>
                                    </form>
                                </details>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>

            @if (! in_array($booking->status, ['checked_in','checked_out'], true))
                <details class="admin-card p-4"><summary class="cursor-pointer text-sm font-black">+ Add another occupant</summary>
                    <form id="addOccupantForm" method="POST" action="{{ route('admin.bookings.guests.store',$booking) }}" class="mt-3 grid gap-2 md:grid-cols-3 lg:grid-cols-4">@csrf
                        <select id="existingGuestProfile" name="user_id" class="h-9 rounded border border-blue-200 bg-blue-50 px-2 text-xs font-bold lg:col-span-2"><option value="">New guest — enter details</option>@foreach($guestProfiles as $profile)<option value="{{ $profile->id }}" data-name="{{ $profile->name }}" data-phone="{{ $profile->phone }}" data-email="{{ $profile->email }}" data-dob="{{ $profile->date_of_birth?->toDateString() }}" data-nationality="{{ $profile->nationality ?: 'Indian' }}" data-address="{{ $profile->address }}">{{ $profile->name }} · {{ $profile->phone ?: $profile->email }}</option>@endforeach</select>
                        <select name="guest_type" class="h-9 rounded border border-slate-300 bg-white px-2 text-xs"><option value="adult">Adult</option><option value="child">Child</option></select><select name="role" class="h-9 rounded border border-slate-300 bg-white px-2 text-xs"><option value="additional">Additional occupant</option><option value="primary">Make primary guest</option></select>
                        <input data-guest-field="name" name="full_name" required placeholder="Full legal name *" class="h-9 rounded border border-slate-300 px-2 text-xs"><input data-guest-field="phone" name="phone" placeholder="Mobile" class="h-9 rounded border border-slate-300 px-2 text-xs"><input data-guest-field="email" name="email" type="email" placeholder="Email" class="h-9 rounded border border-slate-300 px-2 text-xs"><input data-guest-field="dob" name="date_of_birth" type="date" class="h-9 rounded border border-slate-300 px-2 text-xs">
                        <input data-guest-field="nationality" name="nationality" value="Indian" required placeholder="Nationality *" class="h-9 rounded border border-slate-300 px-2 text-xs"><input data-guest-field="address" name="address_line_1" placeholder="Address" class="h-9 rounded border border-slate-300 px-2 text-xs lg:col-span-2"><input name="city" placeholder="City" class="h-9 rounded border border-slate-300 px-2 text-xs"><input name="state" placeholder="State" class="h-9 rounded border border-slate-300 px-2 text-xs"><input name="postal_code" placeholder="Postal code" class="h-9 rounded border border-slate-300 px-2 text-xs"><input name="country" value="India" required placeholder="Country *" class="h-9 rounded border border-slate-300 px-2 text-xs">
                        <label class="flex h-9 items-center gap-2 text-[11px] font-bold text-slate-600"><input type="checkbox" name="same_as_primary" value="1"> Same address as primary</label><button class="h-9 rounded bg-blue-600 px-3 text-xs font-black text-white">Add occupant</button>
                    </form>
                </details>
            @endif
        </div>

        <aside class="space-y-4">
            @if ($booking->status === 'checked_in')
                <form method="POST" action="{{ route('admin.bookings.check-out',$booking) }}" class="admin-card p-4">@csrf<h2 class="text-base font-black">Complete checkout</h2><p class="mt-1 text-xs text-slate-500">Confirm operational settlement before releasing the room.</p><label class="mt-4 flex gap-2 text-sm font-bold"><input type="checkbox" name="balance_confirmed" value="1" required> Folio/balance reviewed</label><label class="mt-2 flex gap-2 text-sm font-bold"><input type="checkbox" name="keys_returned" value="1" required> Keys/cards returned</label><textarea name="notes" rows="3" placeholder="Checkout notes" class="mt-3 w-full rounded border border-slate-300 p-2 text-sm"></textarea><button class="mt-3 h-10 w-full rounded bg-slate-950 text-sm font-black text-white">Complete checkout</button></form>
            @elseif ($booking->status !== 'checked_out')
                <form method="POST" action="{{ route('admin.bookings.check-in',$booking) }}" class="admin-card p-4">@csrf<h2 class="text-base font-black">Complete check-in</h2>
                    @if(!$readiness['ready'])<div class="mt-3 rounded-lg bg-amber-50 p-3"><p class="text-xs font-black text-amber-900">Complete first:</p><ul class="mt-1 list-disc pl-4 text-xs font-semibold text-amber-800">@foreach($readiness['blockers'] as $blocker)<li>{{ $blocker }}</li>@endforeach</ul></div>@endif
                    <label class="mt-4 block text-xs font-black text-slate-600">Physical room</label><select name="room_id" required class="mt-1 h-10 w-full rounded border border-slate-300 bg-white px-3 text-sm"><option value="">Select room</option>@if($booking->room)<option value="{{ $booking->room->id }}" selected>Room {{ $booking->room->room_number }}</option>@endif @foreach($assignableRooms->where('id','!=',$booking->room_id) as $room)<option value="{{ $room->id }}">Room {{ $room->room_number }}{{ $room->floor ? ' · '.$room->floor : '' }}</option>@endforeach</select>
                    <label class="mt-3 flex gap-2 text-xs font-bold text-slate-700"><input type="checkbox" name="registration_accepted" value="1" required> Guest accepted registration details and property policies</label><textarea name="notes" rows="3" placeholder="Check-in notes" class="mt-3 w-full rounded border border-slate-300 p-2 text-sm"></textarea><button @disabled(!$readiness['ready']) class="mt-3 h-10 w-full rounded bg-emerald-600 text-sm font-black text-white disabled:cursor-not-allowed disabled:opacity-40">Check in guest</button>
                </form>
            @endif

            <section class="admin-card p-4"><h2 class="text-sm font-black">Stay timeline</h2><div class="mt-3 space-y-3">@forelse(optional($booking->stay)->events ?? [] as $event)<div class="border-l-2 border-slate-200 pl-3"><p class="text-xs font-black">{{ ucfirst(str_replace('_',' ',$event->event_type)) }}</p><p class="text-[10px] text-slate-500">{{ $event->created_at->format('d M Y, H:i') }} · {{ $event->actor?->name ?? 'System' }}</p></div>@empty<p class="text-xs text-slate-500">No stay events recorded yet.</p>@endforelse</div></section>
        </aside>
    </div>
    <script>
        document.getElementById('existingGuestProfile')?.addEventListener('change', function () {
            const option = this.selectedOptions[0];
            const map = {name:'name', phone:'phone', email:'email', dob:'dob', nationality:'nationality', address:'address'};
            Object.entries(map).forEach(([dataKey, fieldKey]) => {
                const field = document.querySelector(`[data-guest-field="${fieldKey}"]`);
                if (field) field.value = option?.dataset[dataKey] || (fieldKey === 'nationality' ? 'Indian' : '');
            });
        });
    </script>
@endsection
