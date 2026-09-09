<div class="space-y-6">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs text-slate-500">Revenue today</p>
                <x-icon name="currency" class="w-4 h-4 text-sky-500" />
            </div>
            <p class="text-2xl font-bold text-white mt-2">{{ number_format($revenueToday, 0) }}</p>
            <p class="text-[11px] text-slate-500">TZS</p>
        </div>
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs text-slate-500">Revenue this month</p>
                <x-icon name="chart" class="w-4 h-4 text-orange-500" />
            </div>
            <p class="text-2xl font-bold text-white mt-2">{{ number_format($revenueThisMonth, 0) }}</p>
            <p class="text-[11px] text-slate-500">TZS &middot; week: {{ number_format($revenueThisWeek, 0) }}</p>
        </div>
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs text-slate-500">Active sessions</p>
                <x-icon name="signal" class="w-4 h-4 text-emerald-500" />
            </div>
            <p class="text-2xl font-bold text-emerald-400 mt-2">{{ $activeSessionsCount }}</p>
            <p class="text-[11px] text-slate-500">{{ $vouchersIssuedToday }} vouchers issued today</p>
        </div>
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-5">
            <div class="flex items-start justify-between">
                <p class="text-xs text-slate-500">Pending payments</p>
                <x-icon name="bell" class="w-4 h-4 {{ $pendingPaymentsToday > 0 ? 'text-rose-500' : 'text-slate-600' }}" />
            </div>
            <p class="text-2xl font-bold text-white mt-2">{{ $pendingPaymentsToday }}</p>
            <p class="text-[11px] text-slate-500">awaiting confirmation today</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Revenue trend --}}
        <div class="lg:col-span-2 bg-slate-900 rounded-xl border border-slate-800 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-white">Revenue &mdash; last 14 days</h3>
                <span class="flex items-center gap-1.5 text-xs text-slate-400">
                    <span class="w-2.5 h-2.5 rounded-sm" style="background: {{ $seriesColor }}"></span>
                    Revenue (TZS)
                </span>
            </div>

            <svg viewBox="0 0 700 160" class="w-full h-40" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="revenueFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="{{ $seriesColor }}" stop-opacity="0.35" />
                        <stop offset="100%" stop-color="{{ $seriesColor }}" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <path d="{{ $chartPath }}" fill="url(#revenueFill)" stroke="none" />
                <path d="{{ $chartLinePath }}" fill="none" stroke="{{ $seriesColor }}" stroke-width="2" stroke-linecap="round" />
                @foreach ($chart as $i => $point)
                    @php
                        $x = $chart->count() > 1 ? round($i * (700 / ($chart->count() - 1)), 2) : 0;
                        $y = round(160 - ($point['total'] / $chartMax) * 148 - 4, 2);
                    @endphp
                    <circle cx="{{ $x }}" cy="{{ $y }}" r="3" fill="{{ $seriesColor }}">
                        <title>{{ $point['label'] }}: {{ number_format($point['total'], 0) }} TZS</title>
                    </circle>
                @endforeach
            </svg>
            <div class="flex justify-between mt-1 text-[10px] text-slate-500">
                @foreach ($chart as $point)
                    <span>{{ $point['label'] }}</span>
                @endforeach
            </div>
        </div>

        {{-- Voucher status donut --}}
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-6">
            <h3 class="text-sm font-semibold text-white mb-4">Vouchers by status</h3>
            <div class="flex items-center justify-center">
                @php
                    $circumference = 2 * M_PI * 40;
                    $offset = 0;
                @endphp
                <svg viewBox="0 0 100 100" class="w-32 h-32 -rotate-90">
                    <circle cx="50" cy="50" r="40" fill="none" stroke="#1e293b" stroke-width="14" />
                    @foreach ($voucherStatus as $slice)
                        @php
                            $fraction = $slice['total'] / $voucherStatusTotal;
                            $dash = $fraction * $circumference;
                        @endphp
                        @if ($slice['total'] > 0)
                            <circle cx="50" cy="50" r="40" fill="none" stroke="{{ $slice['color'] }}" stroke-width="14"
                                stroke-dasharray="{{ round($dash, 2) }} {{ round($circumference - $dash, 2) }}"
                                stroke-dashoffset="{{ round(-$offset, 2) }}" stroke-linecap="butt">
                                <title>{{ $slice['label'] }}: {{ $slice['total'] }}</title>
                            </circle>
                            @php $offset += $dash; @endphp
                        @endif
                    @endforeach
                </svg>
            </div>
            <div class="mt-4 space-y-2">
                @foreach ($voucherStatus as $slice)
                    <div class="flex items-center justify-between text-xs">
                        <span class="flex items-center gap-2 text-slate-400">
                            <span class="w-2.5 h-2.5 rounded-full" style="background: {{ $slice['color'] }}"></span>
                            {{ $slice['label'] }}
                        </span>
                        <span class="font-medium text-slate-200">{{ $slice['total'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Revenue by plan --}}
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-6">
            <h3 class="text-sm font-semibold text-white mb-4">Top plans this month</h3>
            @if ($revenueByPlan->isEmpty())
                <p class="text-sm text-slate-500">No confirmed payments yet this month.</p>
            @else
                <div class="flex items-end gap-3 h-32">
                    @foreach ($revenueByPlan as $plan)
                        <div class="flex-1 flex flex-col items-center justify-end h-full">
                            <div class="w-full rounded-t transition-all" style="height: {{ max(4, round(($plan['total'] / $revenueByPlanMax) * 100)) }}%; background: {{ $plan['color'] }}"
                                title="{{ $plan['label'] }}: {{ number_format($plan['total'], 0) }} TZS"></div>
                        </div>
                    @endforeach
                </div>
                <div class="flex gap-3 mt-2">
                    @foreach ($revenueByPlan as $plan)
                        <div class="flex-1 text-center">
                            <p class="text-[10px] text-slate-400 truncate">{{ $plan['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Active sessions --}}
        <div class="lg:col-span-2 bg-slate-900 rounded-xl border border-slate-800 p-6">
            <h3 class="text-sm font-semibold text-white mb-4">Active sessions</h3>
            <div class="space-y-3">
                @forelse ($activeSessions as $session)
                    <div class="flex items-center justify-between text-sm border-b border-slate-800/60 pb-2 last:border-0 last:pb-0">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <div>
                                <p class="font-medium text-slate-200">{{ $session->username }}</p>
                                <p class="text-xs text-slate-500">{{ $session->nasipaddress }}</p>
                            </div>
                        </div>
                        <span class="text-xs text-slate-500">{{ $session->acctstarttime?->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No active sessions right now.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
