@props(['value' => null])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-slate-300 mb-1']) }}>
    {{ $value ?? $slot }}
</label>
