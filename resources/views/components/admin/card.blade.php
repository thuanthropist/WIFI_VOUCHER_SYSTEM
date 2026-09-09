@props(['title' => null])

<div {{ $attributes->merge(['class' => 'bg-slate-900 rounded-xl border border-slate-800 p-6']) }}>
    @if ($title)
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-white">{{ $title }}</h3>
            @isset($actions)
                {{ $actions }}
            @endisset
        </div>
    @endif
    {{ $slot }}
</div>
