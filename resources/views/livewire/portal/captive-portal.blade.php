<div class="min-h-screen flex flex-col bg-gradient-to-b from-sky-600 via-sky-500 to-slate-50">
    {{-- Hero --}}
    <div class="px-6 pt-12 pb-20 text-center text-white">
        <div class="w-16 h-16 rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center mx-auto mb-4 ring-1 ring-white/25">
            <x-icon name="wifi" class="w-8 h-8" />
        </div>
        <h1 class="text-2xl font-bold tracking-tight">{{ config('app.name') }}</h1>
        <p class="text-sky-100 text-sm mt-1.5 max-w-xs mx-auto">
            @if ($this->site)
                Connecting at <span class="font-semibold text-white">{{ $this->site->name }}</span>
            @else
                Fast, secure WiFi &mdash; pay and connect in seconds.
            @endif
        </p>
    </div>

    {{-- Card --}}
    <div class="flex-1 px-4 -mt-12 pb-10">
        <div class="max-w-md mx-auto">
            <div class="bg-white rounded-2xl shadow-xl shadow-slate-900/10 ring-1 ring-slate-900/5 overflow-hidden">
                {{-- Tabs --}}
                <div class="flex border-b border-slate-100">
                    <button wire:click="switchTab('buy')" type="button"
                        class="flex-1 flex items-center justify-center gap-1.5 py-4 text-sm font-semibold transition {{ $activeTab === 'buy' ? 'text-sky-600 border-b-2 border-sky-500 -mb-px' : 'text-slate-400 hover:text-slate-600' }}">
                        <x-icon name="card" class="w-4 h-4" />
                        Buy Voucher
                    </button>
                    <button wire:click="switchTab('redeem')" type="button"
                        class="flex-1 flex items-center justify-center gap-1.5 py-4 text-sm font-semibold transition {{ $activeTab === 'redeem' ? 'text-sky-600 border-b-2 border-sky-500 -mb-px' : 'text-slate-400 hover:text-slate-600' }}">
                        <x-icon name="ticket-check" class="w-4 h-4" />
                        I Have a Code
                    </button>
                </div>

                <div class="p-6">
                    @if ($activeTab === 'buy')
                        {{-- ============ STEP: select-plan ============ --}}
                        @if ($step === 'select-plan')
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Choose a plan</p>
                            <div class="space-y-2.5">
                                @php $bestId = $this->bestValuePlanId; @endphp
                                @forelse ($this->plans as $plan)
                                    <button wire:click="selectPlan({{ $plan->id }})" type="button"
                                        class="w-full flex items-center gap-3 text-left p-4 rounded-xl border-2 border-slate-100 hover:border-sky-300 hover:bg-sky-50/50 transition group relative">
                                        @if ($plan->id === $bestId)
                                            <span class="absolute -top-2 right-3 bg-amber-400 text-amber-900 text-[10px] font-bold px-2 py-0.5 rounded-full">BEST VALUE</span>
                                        @endif
                                        <span class="flex-shrink-0 w-11 h-11 rounded-full bg-sky-100 text-sky-600 flex items-center justify-center group-hover:bg-sky-500 group-hover:text-white transition">
                                            <x-icon name="clock" class="w-5 h-5" />
                                        </span>
                                        <span class="flex-1 min-w-0">
                                            <span class="block font-semibold text-slate-900">{{ $plan->name }}</span>
                                            <span class="flex items-center gap-2 text-xs text-slate-500 mt-0.5">
                                                <span>{{ $plan->duration_minutes }} min</span>
                                                @if ($plan->speed_limit_mbps)
                                                    <span class="inline-flex items-center gap-0.5"><x-icon name="bolt" class="w-3 h-3" /> {{ $plan->speed_limit_mbps }} Mbps</span>
                                                @endif
                                            </span>
                                        </span>
                                        <span class="text-right flex-shrink-0">
                                            <span class="block font-bold text-slate-900">{{ number_format($plan->price, 0) }}</span>
                                            <span class="block text-[11px] text-slate-400">{{ $plan->currency }}</span>
                                        </span>
                                    </button>
                                @empty
                                    <p class="text-center text-sm text-slate-500 py-10">No plans are available right now. Please check back shortly.</p>
                                @endforelse
                            </div>
                        @endif

                        {{-- ============ STEP: phone ============ --}}
                        @if ($step === 'phone' && $this->selectedPlan)
                            <button wire:click="backToPlans" type="button" class="flex items-center gap-1 text-xs font-medium text-slate-400 hover:text-slate-600 mb-4">
                                <x-icon name="arrow-left" class="w-3.5 h-3.5" /> Back
                            </button>

                            <div class="bg-sky-50 border border-sky-100 rounded-xl p-4 flex items-center justify-between mb-5">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $this->selectedPlan->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $this->selectedPlan->duration_minutes }} min access</p>
                                </div>
                                <p class="font-bold text-sky-600">{{ number_format($this->selectedPlan->price, 0) }} {{ $this->selectedPlan->currency }}</p>
                            </div>

                            @if ($errorMessage)
                                <div class="bg-rose-50 border border-rose-100 text-rose-600 text-sm rounded-lg px-3 py-2 mb-4">{{ $errorMessage }}</div>
                            @endif

                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Mobile money number</label>
                            <div class="relative">
                                <x-icon name="phone" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                                <input type="tel" wire:model="phoneNumber" inputmode="numeric" placeholder="07XXXXXXXX"
                                    class="w-full pl-10 pr-3 py-3 text-base rounded-xl border border-slate-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 focus:outline-none">
                            </div>
                            @error('phoneNumber') <p class="text-rose-500 text-xs mt-1.5">{{ $message }}</p> @enderror

                            <button wire:click="submitPhone" wire:loading.attr="disabled" wire:target="submitPhone" type="button"
                                class="w-full mt-5 bg-sky-500 hover:bg-sky-600 disabled:opacity-60 text-white font-semibold py-3.5 rounded-xl transition flex items-center justify-center gap-2">
                                <span wire:loading.remove wire:target="submitPhone">Pay &amp; Connect</span>
                                <span wire:loading wire:target="submitPhone">Sending prompt&hellip;</span>
                            </button>
                            <p class="text-center text-[11px] text-slate-400 mt-3">You'll receive a USSD prompt on your phone to confirm payment.</p>
                        @endif

                        {{-- ============ STEP: processing ============ --}}
                        @if ($step === 'processing')
                            <div wire:poll.2s="pollStatus" class="text-center py-6">
                                <div class="w-14 h-14 rounded-full border-4 border-sky-100 border-t-sky-500 animate-spin mx-auto mb-5"></div>
                                <p class="font-semibold text-slate-900">Check your phone</p>
                                <p class="text-sm text-slate-500 mt-1">Approve the mobile money prompt to complete payment.</p>
                                <p class="text-xs text-slate-400 mt-4 animate-pulse">Waiting for confirmation&hellip;</p>
                            </div>
                        @endif

                        {{-- ============ STEP: connecting (auto-login, no code shown) ============ --}}
                        @if ($step === 'connecting')
                            <div class="text-center py-6"
                                x-data
                                x-init="
                                    @if ($this->siteLoginUrl)
                                        $refs.autoLoginForm.submit();
                                    @endif
                                    setTimeout(() => $wire.confirmConnected(), {{ $this->siteLoginUrl ? 1800 : 900 }});
                                ">
                                <div class="w-14 h-14 rounded-full border-4 border-sky-100 border-t-sky-500 animate-spin mx-auto mb-5"></div>
                                <p class="font-semibold text-slate-900">Connecting you to WiFi&hellip;</p>
                                <p class="text-sm text-slate-500 mt-1">Payment confirmed. Setting up your access automatically.</p>

                                @if ($this->siteLoginUrl)
                                    <iframe name="autoLoginFrame" class="hidden" aria-hidden="true"></iframe>
                                    <form x-ref="autoLoginForm" method="POST" target="autoLoginFrame" action="{{ $this->siteLoginUrl }}" class="hidden">
                                        @if ($this->isOmada)
                                            {{-- Omada's External Web Portal (RADIUS) browserauth/radius-auth API --}}
                                            <input type="text" name="clientMac" value="{{ $omadaClientMac }}">
                                            <input type="text" name="clientIp" value="{{ $omadaClientIp }}">
                                            <input type="text" name="apMac" value="{{ $omadaApMac }}">
                                            <input type="text" name="gatewayMac" value="{{ $omadaGatewayMac }}">
                                            <input type="text" name="ssidName" value="{{ $omadaSsidName }}">
                                            <input type="text" name="vid" value="{{ $omadaVid }}">
                                            <input type="text" name="radioId" value="{{ $omadaRadioId }}">
                                            <input type="text" name="authType" value="{{ $this->site->omada_auth_type }}">
                                            <input type="text" name="originUrl" value="{{ $omadaOriginUrl }}">
                                        @endif
                                        <input type="text" name="username" value="{{ $voucherCode }}">
                                        <input type="password" name="password" value="{{ $voucherCode }}">
                                    </form>
                                @endif
                            </div>
                        @endif

                        {{-- ============ STEP: connected (thank you) ============ --}}
                        @if ($step === 'connected')
                            <div class="text-center py-2" x-data="{ copied: false }">
                                <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                                    <x-icon name="check-circle" class="w-8 h-8 text-emerald-600" />
                                </div>
                                <p class="font-bold text-xl text-slate-900">You're connected!</p>
                                <p class="text-sm text-slate-500 mt-1.5">
                                    Thank you for choosing {{ config('app.name') }} &mdash; enjoy your {{ $voucherPlanName }} access.
                                </p>
                                <p class="text-sm text-sky-600 font-medium mt-1">Asante kwa kutumia huduma yetu! &#127881;</p>

                                <div class="bg-slate-50 rounded-xl px-4 py-3 mt-5 text-left">
                                    <p class="text-xs text-slate-500">Plan</p>
                                    <p class="text-sm font-semibold text-slate-900">{{ $voucherPlanName }}</p>
                                    <p class="text-xs text-slate-400 mt-1">Access valid until {{ $voucherExpiresAt }}.</p>
                                </div>

                                <button wire:click="toggleCodeFallback" type="button" class="block mx-auto text-xs text-slate-400 hover:text-slate-600 underline mt-4">
                                    Trouble connecting? Show my access code
                                </button>

                                @if ($showCodeFallback)
                                    <div class="relative mt-3">
                                        <div class="bg-slate-50 border-2 border-dashed border-slate-200 rounded-xl py-3 font-mono text-xl tracking-[0.3em] text-slate-900 font-bold">
                                            {{ $voucherCode }}
                                        </div>
                                        <button type="button"
                                            @click="navigator.clipboard.writeText('{{ $voucherCode }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                            class="absolute top-2 right-2 text-xs font-medium bg-white border border-slate-200 rounded-lg px-2 py-1 text-slate-500 hover:text-sky-600 hover:border-sky-300">
                                            <span x-show="!copied">Copy</span>
                                            <span x-show="copied" x-cloak class="text-emerald-600">Copied!</span>
                                        </button>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-2">Enter this as both username and password on the WiFi login page.</p>
                                @endif

                                <button wire:click="startOver" type="button" class="block mx-auto text-sky-600 hover:text-sky-700 text-sm font-semibold mt-6">
                                    Buy another voucher
                                </button>
                            </div>
                        @endif

                        {{-- ============ STEP: failed ============ --}}
                        @if ($step === 'failed')
                            <div class="text-center py-4">
                                <div class="w-14 h-14 rounded-full bg-rose-100 flex items-center justify-center mx-auto mb-4">
                                    <x-icon name="x-mark" class="w-7 h-7 text-rose-600" />
                                </div>
                                <p class="font-bold text-lg text-slate-900">Payment failed</p>
                                <p class="text-sm text-slate-500 mt-1 mb-5">The payment wasn't completed. No charge was made.</p>
                                <button wire:click="startOver" type="button" class="bg-sky-500 hover:bg-sky-600 text-white font-semibold px-6 py-3 rounded-xl transition">
                                    Try again
                                </button>
                            </div>
                        @endif
                    @else
                        {{-- ============ TAB: redeem / "I have a code" ============ --}}
                        @if (! $redeemResult)
                            <p class="text-sm text-slate-500 mb-4">Already paid at the counter or bought a voucher earlier? Enter the code staff gave you to check its status.</p>

                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Voucher code</label>
                            <div class="relative">
                                <x-icon name="key" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                                <input type="text" wire:model="redeemCode" wire:keydown.enter="lookupVoucher" placeholder="e.g. ZKYJPSU5"
                                    class="w-full pl-10 pr-3 py-3 text-base uppercase tracking-widest font-mono rounded-xl border border-slate-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 focus:outline-none">
                            </div>
                            @error('redeemCode') <p class="text-rose-500 text-xs mt-1.5">{{ $message }}</p> @enderror

                            <button wire:click="lookupVoucher" wire:loading.attr="disabled" wire:target="lookupVoucher" type="button"
                                class="w-full mt-5 bg-slate-900 hover:bg-slate-800 disabled:opacity-60 text-white font-semibold py-3.5 rounded-xl transition">
                                Check code
                            </button>
                        @else
                            <div x-data="{ copied: false }">
                                @if (! $redeemResult['found'])
                                    <div class="text-center py-4">
                                        <div class="w-14 h-14 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
                                            <x-icon name="x-mark" class="w-7 h-7 text-amber-600" />
                                        </div>
                                        <p class="font-bold text-lg text-slate-900">Code not found</p>
                                        <p class="text-sm text-slate-500 mt-1 mb-5">{{ $redeemResult['message'] }}</p>
                                    </div>
                                @else
                                    @php
                                        $statusStyles = match(true) {
                                            $redeemResult['is_expired'] => ['bg' => 'bg-slate-100', 'text' => 'text-slate-500', 'icon' => 'x-mark', 'label' => 'Expired'],
                                            $redeemResult['status'] === 'active' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-600', 'icon' => 'check-circle', 'label' => 'Connected'],
                                            default => ['bg' => 'bg-sky-100', 'text' => 'text-sky-600', 'icon' => 'wifi', 'label' => 'Ready to use'],
                                        };
                                    @endphp
                                    <div class="text-center py-2">
                                        <div class="w-14 h-14 rounded-full {{ $statusStyles['bg'] }} flex items-center justify-center mx-auto mb-4">
                                            <x-icon :name="$statusStyles['icon']" class="w-7 h-7 {{ $statusStyles['text'] }}" />
                                        </div>
                                        <p class="font-bold text-lg text-slate-900">{{ $statusStyles['label'] }}</p>
                                        <p class="text-sm text-slate-500 mt-1 mb-5">{{ $redeemResult['plan_name'] }} plan</p>

                                        <div class="relative">
                                            <div class="bg-slate-50 border-2 border-dashed border-slate-200 rounded-xl py-4 font-mono text-2xl tracking-[0.3em] text-slate-900 font-bold">
                                                {{ $redeemResult['code'] }}
                                            </div>
                                            <button type="button"
                                                @click="navigator.clipboard.writeText('{{ $redeemResult['code'] }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                                class="absolute top-2 right-2 text-xs font-medium bg-white border border-slate-200 rounded-lg px-2 py-1 text-slate-500 hover:text-sky-600 hover:border-sky-300">
                                                <span x-show="!copied">Copy</span>
                                                <span x-show="copied" x-cloak class="text-emerald-600">Copied!</span>
                                            </button>
                                        </div>

                                        @if (! $redeemResult['is_expired'])
                                            <p class="text-xs text-slate-400 mt-3">
                                                @if ($redeemResult['status'] === 'unused')
                                                    Enter this code as both username and password on the WiFi login page. Expires {{ $redeemResult['expires_at'] }}.
                                                @else
                                                    Expires {{ $redeemResult['expires_at'] }}.
                                                @endif
                                            </p>
                                        @else
                                            <p class="text-xs text-slate-400 mt-3">This code can no longer be used &mdash; buy a new voucher to get connected.</p>
                                        @endif
                                    </div>
                                @endif

                                <button wire:click="resetRedeem" type="button" class="w-full text-sky-600 hover:text-sky-700 text-sm font-semibold mt-2 py-2">
                                    Check another code
                                </button>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Trust badges --}}
            <div class="flex items-center justify-center gap-2 mt-6">
                <span class="text-[11px] font-medium bg-white/70 text-slate-500 px-2.5 py-1 rounded-full ring-1 ring-slate-900/5">M-Pesa</span>
                <span class="text-[11px] font-medium bg-white/70 text-slate-500 px-2.5 py-1 rounded-full ring-1 ring-slate-900/5">Airtel Money</span>
                <span class="text-[11px] font-medium bg-white/70 text-slate-500 px-2.5 py-1 rounded-full ring-1 ring-slate-900/5">Tigo Pesa</span>
            </div>

            @if ($supportPhone || $supportEmail)
                <p class="text-center text-xs text-slate-400 mt-4">
                    Need help? {{ $supportPhone }}{{ $supportPhone && $supportEmail ? ' · ' : '' }}{{ $supportEmail }}
                </p>
            @endif
        </div>
    </div>
</div>
