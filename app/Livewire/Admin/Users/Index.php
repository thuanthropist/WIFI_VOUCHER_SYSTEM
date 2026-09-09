<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|min:8')]
    public string $password = '';

    #[Validate('required|in:admin,staff')]
    public string $role = 'staff';

    public bool $is_active = true;

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->role;
        $this->is_active = $user->is_active;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => $this->editingId ? 'nullable|string|min:8' : 'required|string|min:8',
            'role' => 'required|in:admin,staff',
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'is_active' => $this->is_active,
        ];

        if ($this->password !== '') {
            $data['password'] = bcrypt($this->password);
        }

        $user = User::updateOrCreate(['id' => $this->editingId], $data);

        if (! $this->editingId) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $userId): void
    {
        if ($userId === Auth::id()) {
            $this->addError('self', 'You cannot deactivate your own account.');

            return;
        }

        $user = User::findOrFail($userId);
        $user->update(['is_active' => ! $user->is_active]);
    }

    public function delete(int $userId): void
    {
        if ($userId === Auth::id()) {
            $this->addError('self', 'You cannot delete your own account.');

            return;
        }

        User::findOrFail($userId)->delete();
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password']);
        $this->role = 'staff';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.users.index', [
            'users' => User::orderBy('name')->get(),
        ]);
    }
}
