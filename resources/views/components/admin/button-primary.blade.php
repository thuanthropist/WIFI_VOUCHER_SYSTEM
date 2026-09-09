@props(['type' => 'button'])

<button type="{{ $type }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 bg-sky-500 hover:bg-sky-400 text-white text-sm font-medium px-4 py-2 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed']) }}>
    {{ $slot }}
</button>
