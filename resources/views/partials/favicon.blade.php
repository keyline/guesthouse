@php
    $siteIdentity = isset($adminTheme) ? $adminTheme : (\Illuminate\Support\Facades\Schema::hasTable('settings') ? \App\Models\Setting::query()->first() : null);
@endphp
@if($siteIdentity?->favicon_path)
    <link rel="icon" href="{{ asset('storage/'.$siteIdentity->favicon_path) }}">
    <link rel="shortcut icon" href="{{ asset('storage/'.$siteIdentity->favicon_path) }}">
@endif
