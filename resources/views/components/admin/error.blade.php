@props(['messages'])

@if ($messages)
    <p {{ $attributes->merge(['class' => 'text-rose-400 text-xs mt-1']) }}>
        {{ (is_array($messages) ? ($messages[0] ?? '') : $messages) }}
    </p>
@endif
