@extends('admin.layouts.app')

@section('title', 'Activity Log')
@section('eyebrow', 'Audit')
@section('page-title', 'Activity Log')

@section('content')
    <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.activity-log.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label for="subject_type" class="text-xs font-black uppercase tracking-wide text-slate-500">Record type</label>
                <select id="subject_type" name="subject_type" onchange="this.form.submit()" class="mt-1 h-10 w-44 rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold">
                    <option value="">All types</option>
                    @foreach ($subjectTypes as $type)
                        <option value="{{ $type }}" @selected(request('subject_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="action" class="text-xs font-black uppercase tracking-wide text-slate-500">Action</label>
                <select id="action" name="action" onchange="this.form.submit()" class="mt-1 h-10 w-36 rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold">
                    <option value="">All actions</option>
                    @foreach (['created', 'updated', 'deleted'] as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ ucfirst($action) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="property_id" class="text-xs font-black uppercase tracking-wide text-slate-500">Property</label>
                <select id="property_id" name="property_id" onchange="this.form.submit()" class="mt-1 h-10 w-56 rounded-lg border border-slate-300 bg-white px-3 text-sm font-bold">
                    <option value="">All properties</option>
                    @foreach ($properties as $id => $name)
                        <option value="{{ $id }}" @selected(request()->integer('property_id') === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('admin.activity-log.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-bold text-slate-700">Reset</a>
        </form>
    </section>

    <section class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Who</th>
                        <th>Action</th>
                        <th>Record</th>
                        <th>Changes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $tone = ['created' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'updated' => 'bg-sky-50 text-sky-700 ring-sky-200', 'deleted' => 'bg-rose-50 text-rose-700 ring-rose-200'][$log->action] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
                        @endphp
                        <tr class="align-top">
                            <td class="whitespace-nowrap">
                                <span class="font-bold text-slate-800">{{ $log->created_at->format('d M Y') }}</span>
                                <span class="block text-xs text-slate-500">{{ $log->created_at->format('H:i:s') }}</span>
                            </td>
                            <td>
                                <span class="font-bold text-slate-800">{{ $log->user_name }}</span>
                                @if ($log->ip_address)
                                    <span class="block text-xs text-slate-400">{{ $log->ip_address }}</span>
                                @endif
                            </td>
                            <td><span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-black ring-1 {{ $tone }}">{{ $log->action }}</span></td>
                            <td>
                                <span class="font-black text-slate-900">{{ $log->subject_type }}</span>
                                <span class="block text-xs font-semibold text-slate-500">{{ $log->subject_label ?? '#'.$log->subject_id }}{{ $log->property_id && $properties->has($log->property_id) ? ' · '.$properties[$log->property_id] : '' }}</span>
                            </td>
                            <td class="max-w-md whitespace-normal">
                                @if ($log->action === 'updated')
                                    @foreach (collect($log->new_values ?? [])->take(6) as $field => $value)
                                        <span class="block text-xs">
                                            <span class="font-black text-slate-600">{{ $field }}:</span>
                                            <span class="text-rose-600 line-through">{{ is_scalar($log->old_values[$field] ?? null) ? \Illuminate\Support\Str::limit((string) $log->old_values[$field], 40) : '—' }}</span>
                                            →
                                            <span class="font-bold text-emerald-700">{{ is_scalar($value) ? \Illuminate\Support\Str::limit((string) $value, 40) : json_encode($value) }}</span>
                                        </span>
                                    @endforeach
                                @elseif ($log->action === 'created')
                                    <span class="text-xs font-semibold text-slate-500">{{ collect($log->new_values ?? [])->take(5)->map(fn ($v, $k) => $k.': '.(is_scalar($v) ? \Illuminate\Support\Str::limit((string) $v, 30) : '…'))->implode(' · ') }}</span>
                                @else
                                    <span class="text-xs font-semibold text-slate-500">Record removed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center font-semibold text-slate-500">No activity recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">{{ $logs->links() }}</div>
    </section>
@endsection
