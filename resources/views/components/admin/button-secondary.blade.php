@props(['type' => 'button'])

<button type="{{ $type }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-sm font-medium px-4 py-2 rounded-lg transition']) }}>
    {{ $slot }}
</button>
