@props(['disabled' => false])

<select @disabled($disabled) {{ $attributes->merge(['class' => 'w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-sky-500 focus:border-sky-500 disabled:opacity-50']) }}>
    {{ $slot }}
</select>
