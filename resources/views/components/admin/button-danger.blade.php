@props(['type' => 'button'])

<button type="{{ $type }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 bg-rose-600/10 hover:bg-rose-600/20 text-rose-400 border border-rose-600/30 text-sm font-medium px-4 py-2 rounded-lg transition']) }}>
    {{ $slot }}
</button>
