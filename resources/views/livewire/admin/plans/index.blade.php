<div>
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-slate-500">Create and manage the WiFi access plans customers can buy.</p>
        <x-admin.button-primary wire:click="create">
            <x-icon name="plus" class="w-4 h-4" /> New Plan
        </x-admin.button-primary>
    </div>

    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-800/50 text-slate-400 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Name</th>
                    <th class="text-left px-4 py-3">Price</th>
                    <th class="text-left px-4 py-3">Duration</th>
                    <th class="text-left px-4 py-3">Speed</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-right px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($plans as $plan)
                    <tr wire:key="plan-{{ $plan->id }}" class="hover:bg-slate-800/30">
                        <td class="px-4 py-3 font-medium text-white">{{ $plan->name }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ number_format($plan->price, 0) }} {{ $plan->currency }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $plan->duration_minutes }} min</td>
                        <td class="px-4 py-3 text-slate-300">{!! $plan->speed_limit_mbps ? $plan->speed_limit_mbps.' Mbps' : '&mdash;' !!}</td>
                        <td class="px-4 py-3">
                            <button wire:click="toggleActive({{ $plan->id }})" type="button"
                                class="text-xs px-2 py-1 rounded-full font-medium {{ $plan->is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-700/50 text-slate-400' }}">
                                {{ $plan->is_active ? 'Active' : 'Disabled' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <button wire:click="edit({{ $plan->id }})" type="button" class="text-sky-400 hover:text-sky-300 text-sm font-medium">Edit</button>
                            <button wire:click="delete({{ $plan->id }})" type="button" wire:confirm="Delete this plan?" class="text-rose-400 hover:text-rose-300 text-sm font-medium">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No plans yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-admin.modal :show="$showModal" :title="$editingId ? 'Edit Plan' : 'New Plan'" close="closeModal">
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <x-admin.label value="Name" />
                <x-admin.input wire:model="name" />
                <x-admin.error :messages="$errors->get('name')" />
            </div>
            <div>
                <x-admin.label value="Price" />
                <x-admin.input wire:model="price" type="number" step="0.01" />
                <x-admin.error :messages="$errors->get('price')" />
            </div>
            <div>
                <x-admin.label value="Currency" />
                <x-admin.input wire:model="currency" maxlength="3" />
                <x-admin.error :messages="$errors->get('currency')" />
            </div>
            <div>
                <x-admin.label value="Duration (minutes)" />
                <x-admin.input wire:model="duration_minutes" type="number" />
                <x-admin.error :messages="$errors->get('duration_minutes')" />
            </div>
            <div>
                <x-admin.label value="Speed limit (Mbps)" />
                <x-admin.input wire:model="speed_limit_mbps" type="number" />
                <x-admin.error :messages="$errors->get('speed_limit_mbps')" />
            </div>
            <div class="col-span-2">
                <x-admin.label value="Description" />
                <textarea wire:model="description" rows="2" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-sky-500 focus:border-sky-500"></textarea>
            </div>
            <div class="col-span-2 flex items-center gap-2">
                <input type="checkbox" wire:model="is_active" id="is_active" class="rounded border-slate-600 bg-slate-800 text-sky-500 focus:ring-sky-500">
                <label for="is_active" class="text-sm text-slate-300">Active (visible on the captive portal)</label>
            </div>
        </div>

        <x-slot:footer>
            <x-admin.button-secondary wire:click="closeModal">Cancel</x-admin.button-secondary>
            <x-admin.button-primary wire:click="save">Save</x-admin.button-primary>
        </x-slot:footer>
    </x-admin.modal>
</div>
