@extends('admin.layouts.app')

@section('title', 'Banquet Leads')
@section('eyebrow', 'Marketing')
@section('page-title', 'Banquet Leads')

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('status') }}</div>
    @endif

    {{-- Stat chips + filter --}}
    <section class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.banquet-leads.index') }}" class="rounded-lg px-3 py-2 text-[11px] font-black {{ ! request('status') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">All</a>
            <a href="{{ route('admin.banquet-leads.index', ['status' => 'new']) }}" class="rounded-lg px-3 py-2 text-[11px] font-black {{ request('status') === 'new' ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-700' }}">New · {{ $counts['new'] }}</a>
            <a href="{{ route('admin.banquet-leads.index', ['status' => 'contacted']) }}" class="rounded-lg px-3 py-2 text-[11px] font-black {{ request('status') === 'contacted' ? 'bg-sky-600 text-white' : 'bg-sky-50 text-sky-700' }}">Contacted · {{ $counts['contacted'] }}</a>
            <a href="{{ route('admin.banquet-leads.index', ['status' => 'closed']) }}" class="rounded-lg px-3 py-2 text-[11px] font-black {{ request('status') === 'closed' ? 'bg-slate-600 text-white' : 'bg-slate-100 text-slate-600' }}">Closed · {{ $counts['closed'] }}</a>
        </div>
        <span class="text-xs font-bold text-slate-500">{{ $leads->total() }} {{ Str::plural('lead', $leads->total()) }}</span>
    </section>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Guest</th>
                        <th>Banquet</th>
                        <th>Event</th>
                        <th>Received</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leads as $lead)
                        <tr>
                            <td class="align-top">
                                <p class="font-black text-slate-900">{{ $lead->name }}</p>
                                <p class="text-xs font-bold text-slate-600"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $lead->phone) }}" class="text-sky-700 no-underline hover:underline">{{ $lead->phone }}</a></p>
                                @if ($lead->email)<p class="text-[11px] font-semibold text-slate-500">{{ $lead->email }}</p>@endif
                            </td>
                            <td class="align-top">
                                <p class="font-bold text-slate-800">{{ $lead->banquet?->name ?? '—' }}</p>
                                <p class="text-[11px] font-semibold text-slate-500">{{ $lead->property?->name }}</p>
                            </td>
                            <td class="align-top text-xs font-semibold text-slate-600">
                                <p class="capitalize">{{ $lead->event_type ? str_replace('_', ' ', $lead->event_type) : 'Event' }}</p>
                                <p>{{ $lead->event_date ?: '—' }}{{ $lead->guest_count ? ' · '.$lead->guest_count.' guests' : '' }}</p>
                                @if ($lead->message)<p class="mt-1 max-w-xs truncate text-[11px] font-medium text-slate-400" title="{{ $lead->message }}">“{{ $lead->message }}”</p>@endif
                            </td>
                            <td class="align-top text-xs font-semibold text-slate-500">{{ $lead->created_at->format('d M Y') }}<br>{{ $lead->created_at->diffForHumans() }}</td>
                            <td class="align-top">
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-black
                                    @if ($lead->status === 'new') bg-amber-100 text-amber-800
                                    @elseif ($lead->status === 'contacted') bg-sky-100 text-sky-800
                                    @else bg-slate-200 text-slate-600 @endif">{{ $statuses[$lead->status] ?? ucfirst($lead->status) }}</span>
                            </td>
                            <td class="align-top text-right">
                                <div class="inline-flex flex-wrap justify-end gap-1.5">
                                    @foreach ($statuses as $value => $label)
                                        @if ($value !== $lead->status)
                                            <form method="POST" action="{{ route('admin.banquet-leads.update-status', $lead) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $value }}">
                                                <button class="rounded-lg border border-slate-300 px-2.5 py-1 text-[11px] font-black text-slate-600 hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700">Mark {{ $label }}</button>
                                            </form>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center">
                                <p class="text-sm font-bold text-slate-600">No banquet leads yet</p>
                                <p class="mt-1 text-xs font-semibold text-slate-400">Enquiries from banquet pages will appear here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6">{{ $leads->links() }}</div>
@endsection
