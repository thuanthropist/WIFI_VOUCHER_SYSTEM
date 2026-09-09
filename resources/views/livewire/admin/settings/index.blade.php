<div x-data="{ toast: false }" @settings-saved.window="toast = true; setTimeout(() => toast = false, 3000)" class="max-w-3xl">
    <div x-show="toast" x-transition x-cloak class="mb-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm rounded-lg px-4 py-3">
        Settings saved.
    </div>

    <x-admin.card title="Branding">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <x-admin.label value="Application name" />
                <x-admin.input wire:model="app_name" />
                <x-admin.error :messages="$errors->get('app_name')" />
                <p class="text-[11px] text-slate-500 mt-1">Shown in the browser tab and admin sidebar.</p>
            </div>
            <div>
                <x-admin.label value="Support phone" />
                <x-admin.input wire:model="support_phone" placeholder="+255 7XX XXX XXX" />
                <x-admin.error :messages="$errors->get('support_phone')" />
            </div>
            <div>
                <x-admin.label value="Support email" />
                <x-admin.input wire:model="support_email" type="email" />
                <x-admin.error :messages="$errors->get('support_email')" />
            </div>
        </div>
    </x-admin.card>

    <x-admin.card title="Billing" class="mt-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-admin.label value="Currency" />
                <x-admin.input wire:model="currency" maxlength="3" />
                <x-admin.error :messages="$errors->get('currency')" />
            </div>
            <div>
                <x-admin.label value="Default site" />
                <x-admin.select wire:model="default_site_id">
                    <option value="">None</option>
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </x-admin.select>
            </div>
            <div class="md:col-span-2">
                <x-admin.label value="Voucher redeem window multiplier" />
                <x-admin.input wire:model="voucher_redeem_multiplier" type="number" min="1" max="10" />
                <x-admin.error :messages="$errors->get('voucher_redeem_multiplier')" />
                <p class="text-[11px] text-slate-500 mt-1">A voucher must be redeemed within (plan duration &times; this number) minutes, minimum 60. E.g. a 60-minute plan with multiplier 3 gives customers 3 hours to connect before the code expires unused.</p>
            </div>
        </div>
    </x-admin.card>

    <div class="mt-6">
        <x-admin.button-primary wire:click="save">Save settings</x-admin.button-primary>
    </div>
</div>
