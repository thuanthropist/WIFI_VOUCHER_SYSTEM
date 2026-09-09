<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }} &mdash; Admin</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-slate-950 text-slate-300" x-data="{ collapsed: false, mobileOpen: false }">
        @php
            $navGroups = [
                'General' => [
                    ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
                ],
                'Management' => [
                    ['route' => 'admin.plans.index', 'label' => 'Plans', 'icon' => 'ticket'],
                    ['route' => 'admin.sites.index', 'label' => 'Sites', 'icon' => 'building'],
                    ['route' => 'admin.vouchers.index', 'label' => 'Vouchers', 'icon' => 'key'],
                ],
                'Administration' => array_filter([
                    auth()->user()->isAdmin() ? ['route' => 'admin.users.index', 'label' => 'Users', 'icon' => 'users'] : null,
                    auth()->user()->isAdmin() ? ['route' => 'admin.settings.index', 'label' => 'Settings', 'icon' => 'settings'] : null,
                ]),
            ];
            $allLinks = collect($navGroups)->flatten(1);
            $currentLabel = $allLinks->firstWhere('route', request()->route()?->getName())['label'] ?? 'Admin';
            $attentionCount = \App\Models\Payment::where('status', 'pending')
                ->where('created_at', '<', now()->subMinutes(5))
                ->count();
        @endphp

        <div class="min-h-screen flex">
            {{-- Sidebar --}}
            <aside
                :class="{ 'md:w-20': collapsed, 'md:w-64': !collapsed, '-translate-x-full md:translate-x-0': !mobileOpen, 'translate-x-0': mobileOpen }"
                class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col bg-slate-900 border-r border-slate-800 transition-all duration-200 ease-in-out"
            >
                <div class="h-16 flex items-center justify-between px-4 border-b border-slate-800">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 overflow-hidden">
                        <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-sky-500 flex items-center justify-center text-white">
                            <x-icon name="wifi" class="w-5 h-5" />
                        </span>
                        <span class="font-semibold text-white tracking-tight whitespace-nowrap" x-show="!collapsed" x-cloak>{{ config('app.name') }}</span>
                    </a>
                    <button @click="collapsed = !collapsed" type="button" class="hidden md:flex p-1 rounded-md text-slate-400 hover:text-white hover:bg-slate-800">
                        <span class="inline-block transition-transform" :class="collapsed ? 'rotate-180' : ''">
                            <x-icon name="chevron-left" class="w-4 h-4" />
                        </span>
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-6">
                    @foreach ($navGroups as $group => $links)
                        @if (count($links))
                            <div>
                                <p class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500" x-show="!collapsed" x-cloak>{{ $group }}</p>
                                <div class="space-y-1">
                                    @foreach ($links as $link)
                                        <a href="{{ route($link['route']) }}"
                                           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs($link['route']) ? 'bg-sky-500/10 text-sky-400' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                                            <x-icon :name="$link['icon']" class="w-5 h-5 flex-shrink-0" />
                                            <span class="whitespace-nowrap" x-show="!collapsed" x-cloak>{{ $link['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </nav>

                <div class="p-3 border-t border-slate-800">
                    <div class="flex items-center gap-3 px-2 py-2">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-xs font-semibold text-white">
                            {{ collect(explode(' ', auth()->user()->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}
                        </span>
                        <div class="overflow-hidden" x-show="!collapsed" x-cloak>
                            <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-500 capitalize">{{ auth()->user()->role }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-1">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-slate-400 hover:bg-slate-800 hover:text-white">
                            <x-icon name="logout" class="w-5 h-5 flex-shrink-0" />
                            <span class="whitespace-nowrap" x-show="!collapsed" x-cloak>Log out</span>
                        </button>
                    </form>
                </div>
            </aside>

            {{-- Mobile backdrop --}}
            <div x-show="mobileOpen" x-cloak @click="mobileOpen = false" class="fixed inset-0 bg-slate-950/70 z-40 md:hidden"></div>

            {{-- Main --}}
            <div class="flex-1 flex flex-col min-w-0" :class="collapsed ? 'md:ml-20' : 'md:ml-64'" style="transition: margin .2s ease-in-out;">
                <header class="h-16 flex-shrink-0 bg-slate-900/70 backdrop-blur border-b border-slate-800 flex items-center gap-4 px-4 md:px-6 sticky top-0 z-30">
                    <button @click="mobileOpen = true" type="button" class="md:hidden p-2 rounded-lg text-slate-400 hover:bg-slate-800">
                        <x-icon name="menu" class="w-5 h-5" />
                    </button>

                    <h1 class="text-base font-semibold text-white hidden sm:block">{{ $currentLabel }}</h1>

                    <div class="flex-1 max-w-md" x-data="{ q: '', open: false }" @click.outside="open = false">
                        <div class="relative">
                            <x-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" />
                            <input
                                type="text"
                                x-model="q"
                                @focus="open = true"
                                placeholder="Jump to a page..."
                                class="w-full bg-slate-800/60 border border-slate-700 rounded-lg pl-9 pr-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-sky-500 focus:border-sky-500"
                            >
                            <div x-show="open && q.length" x-cloak class="absolute mt-1 w-full bg-slate-800 border border-slate-700 rounded-lg shadow-xl overflow-hidden z-40">
                                @foreach ($allLinks as $link)
                                    <a href="{{ route($link['route']) }}"
                                       x-show="'{{ strtolower($link['label']) }}'.includes(q.toLowerCase())"
                                       class="flex items-center gap-2 px-3 py-2 text-sm text-slate-300 hover:bg-slate-700 hover:text-white">
                                        <x-icon :name="$link['icon']" class="w-4 h-4" />
                                        {{ $link['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="ml-auto flex items-center gap-1">
                        <a href="{{ route('admin.vouchers.index') }}" title="Payments awaiting confirmation" class="relative p-2 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white">
                            <x-icon name="bell" class="w-5 h-5" />
                            @if ($attentionCount > 0)
                                <span class="absolute top-1 right-1 w-4 h-4 rounded-full bg-rose-500 text-[10px] leading-4 text-center text-white font-semibold">{{ min(99, $attentionCount) }}</span>
                            @endif
                        </a>

                        <div class="flex items-center gap-2 pl-3 ml-1 border-l border-slate-800">
                            <span class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-xs font-semibold text-white">
                                {{ collect(explode(' ', auth()->user()->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}
                            </span>
                            <div class="hidden sm:block leading-tight">
                                <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500 capitalize">{{ auth()->user()->role }}</p>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="flex-1 p-4 md:p-6 max-w-[1600px] w-full mx-auto">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
