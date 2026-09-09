<div>
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-slate-500">Register the routers/controllers (any vendor) allowed to authenticate against this RADIUS server.</p>
        <x-admin.button-primary wire:click="create">
            <x-icon name="plus" class="w-4 h-4" /> New Site
        </x-admin.button-primary>
    </div>

    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-800/50 text-slate-400 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Name</th>
                    <th class="text-left px-4 py-3">NAS IP</th>
                    <th class="text-left px-4 py-3">Vendor</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-right px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($sites as $site)
                    <tr wire:key="site-{{ $site->id }}" class="hover:bg-slate-800/30">
                        <td class="px-4 py-3 font-medium text-white">
                            {{ $site->name }}
                            @if ($site->location)
                                <span class="block text-xs text-slate-500 font-normal">{{ $site->location }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-300 font-mono text-xs">{{ $site->radius_nas_ip }}</td>
                        <td class="px-4 py-3 text-slate-300 capitalize">{{ $site->device_vendor }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-1 rounded-full font-medium {{ $site->is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-700/50 text-slate-400' }}">
                                {{ $site->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <button wire:click="edit({{ $site->id }})" type="button" class="text-sky-400 hover:text-sky-300 text-sm font-medium">Edit</button>
                            <button wire:click="delete({{ $site->id }})" type="button" wire:confirm="Delete this site?" class="text-rose-400 hover:text-rose-300 text-sm font-medium">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No sites registered yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-admin.modal :show="$showModal" :title="$editingId ? 'Edit Site' : 'New Site'" close="closeModal">
        <div class="space-y-4">
            <div>
                <x-admin.label value="Site name" />
                <x-admin.input wire:model="name" placeholder="Main Branch WiFi" />
                <x-admin.error :messages="$errors->get('name')" />
            </div>
            <div>
                <x-admin.label value="Location (optional)" />
                <x-admin.input wire:model="location" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-admin.label value="Router / NAS IP" />
                    <x-admin.input wire:model="radius_nas_ip" class="font-mono" placeholder="10.10.0.1" />
                    <x-admin.error :messages="$errors->get('radius_nas_ip')" />
                </div>
                <div>
                    <x-admin.label value="Device vendor" />
                    <x-admin.select wire:model.live="device_vendor">
                        <option value="mikrotik">MikroTik</option>
                        <option value="omada">TP-Link Omada</option>
                        <option value="unifi">Ubiquiti UniFi</option>
                        <option value="openwrt">OpenWrt</option>
                    </x-admin.select>
                </div>
            </div>
            <div>
                <x-admin.label value="Router login URL (optional)" />
                @if ($device_vendor === 'omada')
                    <x-admin.input wire:model="login_url" class="font-mono text-xs" placeholder="https://your-controller:8043/portal/radius/browserauth" />
                    <x-admin.error :messages="$errors->get('login_url')" />
                    <p class="text-[11px] text-slate-500 mt-1">
                        For Omada, this must be the <strong>Controller's</strong> RADIUS external-portal endpoint (not the AP/gateway IP) &mdash;
                        <code>/portal/radius/browserauth</code> on 5.3.1+, or <code>/portal/radius/auth</code> (JSON) on 4.1.5&ndash;5.1.0.
                    </p>
                @else
                    <x-admin.input wire:model="login_url" class="font-mono text-xs" placeholder="http://10.10.0.1/login" />
                    <x-admin.error :messages="$errors->get('login_url')" />
                    <p class="text-[11px] text-slate-500 mt-1">The router's own captive-portal login form action. When set, the portal auto-connects the customer instead of showing them the voucher code.</p>
                @endif
            </div>
            @if ($device_vendor === 'omada')
                <div>
                    <x-admin.label value="Omada authType value" />
                    <x-admin.input wire:model="omada_auth_type" class="font-mono text-xs" placeholder="e.g. 2" />
                    <x-admin.error :messages="$errors->get('omada_auth_type')" />
                    <p class="text-[11px] text-amber-400 mt-1">
                        &#9888; Unverified against a real controller &mdash; TP-Link's docs only link out to a downloadable demo package rather than showing this value inline.
                        Confirm it from TP-Link's official sample code for your controller version, or from a real client redirect, before relying on this in production.
                    </p>
                </div>
            @endif
            <div>
                <x-admin.label value="RADIUS shared secret" />
                <div class="flex gap-2">
                    <x-admin.input wire:model="shared_secret" class="font-mono text-xs" />
                    <x-admin.button-secondary wire:click="regenerateSecret" class="flex-shrink-0">Regenerate</x-admin.button-secondary>
                </div>
                <x-admin.error :messages="$errors->get('shared_secret')" />
                <p class="text-[11px] text-slate-500 mt-1">Configure this exact secret on the router's RADIUS client settings. Stored encrypted at rest.</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" wire:model="is_active" id="site_is_active" class="rounded border-slate-600 bg-slate-800 text-sky-500 focus:ring-sky-500">
                <label for="site_is_active" class="text-sm text-slate-300">Active</label>
            </div>
        </div>

        <x-slot:footer>
            <x-admin.button-secondary wire:click="closeModal">Cancel</x-admin.button-secondary>
            <x-admin.button-primary wire:click="save">Save</x-admin.button-primary>
        </x-slot:footer>
    </x-admin.modal>
</div>
