<div>
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-slate-500">All vouchers issued, online or manually.</p>
        <x-admin.button-primary wire:click="openIssueModal">
            <x-icon name="plus" class="w-4 h-4" /> Issue Voucher
        </x-admin.button-primary>
    </div>

    <div class="bg-slate-900 rounded-xl border border-slate-800 p-4 mb-6 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
            <x-admin.select wire:model.live="status" class="!py-1.5">
                <option value="">All</option>
                <option value="unused">Unused</option>
                <option value="active">Active</option>
                <option value="expired">Expired</option>
            </x-admin.select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Site</label>
            <x-admin.select wire:model.live="siteId" class="!py-1.5">
                <option value="">All sites</option>
                @foreach ($sites as $site)
                    <option value="{{ $site->id }}">{{ $site->name }}</option>
                @endforeach
            </x-admin.select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">From</label>
            <x-admin.input type="date" wire:model.live="from" class="!py-1.5" />
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">To</label>
            <x-admin.input type="date" wire:model.live="to" class="!py-1.5" />
        </div>
        <button wire:click="resetFilters" type="button" class="text-sm text-slate-500 hover:text-slate-300 mb-2">Clear filters</button>
    </div>

    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-800/50 text-slate-400 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Code</th>
                    <th class="text-left px-4 py-3">Plan</th>
                    <th class="text-left px-4 py-3">Site</th>
                    <th class="text-left px-4 py-3">Phone</th>
                    <th class="text-left px-4 py-3">Source</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-left px-4 py-3">Issued</th>
                    <th class="text-left px-4 py-3">Expires</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($vouchers as $voucher)
                    <tr wire:key="voucher-{{ $voucher->id }}" class="hover:bg-slate-800/30">
                        <td class="px-4 py-3 font-mono text-xs font-medium text-white">{{ $voucher->code }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $voucher->plan?->name }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $voucher->site?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $voucher->payment?->phone_number }}</td>
                        <td class="px-4 py-3">
                            @if ($voucher->payment?->gateway === 'manual')
                                <span class="text-xs px-2 py-1 rounded-full font-medium bg-violet-500/10 text-violet-400">Manual</span>
                            @else
                                <span class="text-xs px-2 py-1 rounded-full font-medium bg-sky-500/10 text-sky-400">Online</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $badge = match($voucher->status) {
                                    'active' => 'bg-emerald-500/10 text-emerald-400',
                                    'expired' => 'bg-slate-700/50 text-slate-400',
                                    default => 'bg-amber-500/10 text-amber-400',
                                };
                            @endphp
                            <span class="text-xs px-2 py-1 rounded-full font-medium {{ $badge }}">{{ ucfirst($voucher->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs">{{ $voucher->created_at->format('d M, H:i') }}</td>
                        <td class="px-4 py-3 text-slate-500 text-xs">{{ $voucher->expires_at?->format('d M, H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">No vouchers match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 text-slate-400">
        {{ $vouchers->links() }}
    </div>

    <x-admin.modal :show="$showIssueModal" title="Issue Voucher" close="closeIssueModal">
        @if (count($issuedCodes))
            <div class="text-center py-2">
                <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center mx-auto mb-3">
                    <x-icon name="check-circle" class="w-6 h-6 text-emerald-400" />
                </div>
                <p class="text-sm text-slate-300 mb-4">{{ count($issuedCodes) }} voucher(s) issued and provisioned on RADIUS:</p>
                <div class="space-y-2">
                    @foreach ($issuedCodes as $code)
                        <div class="bg-slate-800 border border-slate-700 rounded-lg py-2 font-mono text-sm tracking-widest text-white">{{ $code }}</div>
                    @endforeach
                </div>
            </div>

            <x-slot:footer>
                <x-admin.button-secondary wire:click="openIssueModal">Issue more</x-admin.button-secondary>
                <x-admin.button-primary wire:click="closeIssueModal">Done</x-admin.button-primary>
            </x-slot:footer>
        @else
            <div class="space-y-4">
                <p class="text-xs text-slate-500">Issues a voucher directly (cash payment, courtesy access, testing) &mdash; skips the payment gateway but provisions RADIUS exactly like an online sale.</p>
                <div>
                    <x-admin.label value="Plan" />
                    <x-admin.select wire:model="issuePlanId">
                        <option value="">Select a plan&hellip;</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} &mdash; {{ number_format($plan->price, 0) }} {{ $plan->currency }}</option>
                        @endforeach
                    </x-admin.select>
                    <x-admin.error :messages="$errors->get('issuePlanId')" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-admin.label value="Site (optional)" />
                        <x-admin.select wire:model="issueSiteId">
                            <option value="">No specific site</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->name }}</option>
                            @endforeach
                        </x-admin.select>
                    </div>
                    <div>
                        <x-admin.label value="Quantity" />
                        <x-admin.input wire:model="issueQuantity" type="number" min="1" max="20" />
                        <x-admin.error :messages="$errors->get('issueQuantity')" />
                    </div>
                </div>
                <div>
                    <x-admin.label value="Customer phone (optional)" />
                    <x-admin.input wire:model="issuePhone" placeholder="07XXXXXXXX" />
                    <x-admin.error :messages="$errors->get('issuePhone')" />
                </div>
                <div>
                    <x-admin.label value="Note (optional)" />
                    <x-admin.input wire:model="issueNote" placeholder="e.g. Cash paid at front desk" />
                </div>
            </div>

            <x-slot:footer>
                <x-admin.button-secondary wire:click="closeIssueModal">Cancel</x-admin.button-secondary>
                <x-admin.button-primary wire:click="issueVouchers">Issue</x-admin.button-primary>
            </x-slot:footer>
        @endif
    </x-admin.modal>
</div>
