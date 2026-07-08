<section class="admin-card">
    <div class="admin-card-header">
        <div>
            <h3 class="text-sm font-black text-slate-950">{{ $title }}</h3>
            @isset($subtitle)
                <p class="mt-0.5 text-xs font-semibold text-slate-500">{{ $subtitle }}</p>
            @endisset
        </div>
        @isset($action)
            <span class="text-xs font-black text-sky-700">{{ $action }}</span>
        @endisset
    </div>
    <div class="p-4">
        {{ $slot }}
    </div>
</section>
