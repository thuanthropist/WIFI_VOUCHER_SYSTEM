<div>
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-slate-500">Admins manage everything; staff can run vouchers/plans/sites but not users or settings.</p>
        <x-admin.button-primary wire:click="create">
            <x-icon name="plus" class="w-4 h-4" /> New User
        </x-admin.button-primary>
    </div>

    @error('self')
        <div class="bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm rounded-lg px-4 py-3 mb-4">{{ $message }}</div>
    @enderror

    <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-800/50 text-slate-400 text-xs uppercase tracking-wide">
                <tr>
                    <th class="text-left px-4 py-3">Name</th>
                    <th class="text-left px-4 py-3">Email</th>
                    <th class="text-left px-4 py-3">Role</th>
                    <th class="text-left px-4 py-3">Status</th>
                    <th class="text-right px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @foreach ($users as $user)
                    <tr wire:key="user-{{ $user->id }}" class="hover:bg-slate-800/30">
                        <td class="px-4 py-3 font-medium text-white flex items-center gap-2">
                            <span class="w-7 h-7 rounded-full bg-slate-700 flex items-center justify-center text-[11px] font-semibold text-white flex-shrink-0">
                                {{ collect(explode(' ', $user->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}
                            </span>
                            {{ $user->name }}
                            @if ($user->id === auth()->id())
                                <span class="text-[10px] text-slate-500">(you)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-300">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-1 rounded-full font-medium capitalize {{ $user->role === 'admin' ? 'bg-sky-500/10 text-sky-400' : 'bg-slate-700/50 text-slate-300' }}">{{ $user->role }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <button wire:click="toggleActive({{ $user->id }})" type="button"
                                class="text-xs px-2 py-1 rounded-full font-medium {{ $user->is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-700/50 text-slate-400' }}">
                                {{ $user->is_active ? 'Active' : 'Disabled' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <button wire:click="edit({{ $user->id }})" type="button" class="text-sky-400 hover:text-sky-300 text-sm font-medium">Edit</button>
                            <button wire:click="delete({{ $user->id }})" type="button" wire:confirm="Delete this user?" class="text-rose-400 hover:text-rose-300 text-sm font-medium">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <x-admin.modal :show="$showModal" :title="$editingId ? 'Edit User' : 'New User'" close="closeModal">
        <div class="space-y-4">
            <div>
                <x-admin.label value="Name" />
                <x-admin.input wire:model="name" />
                <x-admin.error :messages="$errors->get('name')" />
            </div>
            <div>
                <x-admin.label value="Email" />
                <x-admin.input wire:model="email" type="email" />
                <x-admin.error :messages="$errors->get('email')" />
            </div>
            <div>
                <x-admin.label :value="$editingId ? 'New password (leave blank to keep current)' : 'Password'" />
                <x-admin.input wire:model="password" type="password" />
                <x-admin.error :messages="$errors->get('password')" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-admin.label value="Role" />
                    <x-admin.select wire:model="role">
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </x-admin.select>
                </div>
                <div class="flex items-end pb-2">
                    <label class="flex items-center gap-2 text-sm text-slate-300">
                        <input type="checkbox" wire:model="is_active" class="rounded border-slate-600 bg-slate-800 text-sky-500 focus:ring-sky-500">
                        Active
                    </label>
                </div>
            </div>
        </div>

        <x-slot:footer>
            <x-admin.button-secondary wire:click="closeModal">Cancel</x-admin.button-secondary>
            <x-admin.button-primary wire:click="save">Save</x-admin.button-primary>
        </x-slot:footer>
    </x-admin.modal>
</div>
