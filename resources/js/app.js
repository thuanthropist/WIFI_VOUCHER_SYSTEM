import './bootstrap';

// Livewire v3 already bundles, exposes (window.Alpine), and starts its own
// Alpine instance via @livewireScripts. Starting a second one here causes
// "Detected multiple instances of Alpine running" and silently breaks
// wire:model hydration (Livewire's plugins never attach to the winning
// instance), so this stack must NOT import/start Alpine itself.
