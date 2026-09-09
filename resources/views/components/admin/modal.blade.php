@props(['show' => false, 'title' => '', 'close' => '', 'maxWidth' => 'lg'])

@php
    $maxWidthClass = [
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
    ][$maxWidth] ?? 'max-w-lg';
@endphp

@if ($show)
    <div class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 overflow-y-auto">
        <div class="fixed inset-0 bg-slate-950/70" @if ($close) wire:click="{{ $close }}" @endif></div>
        <div class="relative bg-slate-900 border border-slate-800 rounded-xl shadow-2xl w-full {{ $maxWidthClass }} max-h-full overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
                <h2 class="text-base font-semibold text-white">{{ $title }}</h2>
                @if ($close)
                    <button type="button" wire:click="{{ $close }}" class="text-slate-500 hover:text-white">
                        <x-icon name="x-mark" class="w-5 h-5" />
                    </button>
                @endif
            </div>
            <div class="p-6">
                {{ $slot }}
            </div>
            @isset($footer)
                <div class="flex justify-end gap-2 px-6 py-4 border-t border-slate-800">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
@endif
