<?php

namespace App\Livewire\Admin\Sites;

use App\Models\Site;
use Illuminate\Support\Str;
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

    #[Validate('nullable|string|max:255')]
    public string $location = '';

    #[Validate('required|ip')]
    public string $radius_nas_ip = '';

    #[Validate('required|string|max:255')]
    public string $shared_secret = '';

    #[Validate('required|in:mikrotik,omada,unifi,openwrt')]
    public string $device_vendor = 'mikrotik';

    #[Validate('nullable|url|max:255')]
    public string $login_url = '';

    #[Validate('nullable|string|max:20')]
    public string $omada_auth_type = '';

    public bool $is_active = true;

    public function create(): void
    {
        $this->resetForm();
        $this->shared_secret = Str::random(32);
        $this->showModal = true;
    }

    public function edit(int $siteId): void
    {
        $site = Site::findOrFail($siteId);

        $this->editingId = $site->id;
        $this->name = $site->name;
        $this->location = (string) $site->location;
        $this->radius_nas_ip = $site->radius_nas_ip;
        $this->shared_secret = $site->shared_secret;
        $this->device_vendor = $site->device_vendor;
        $this->login_url = (string) $site->login_url;
        $this->omada_auth_type = (string) $site->omada_auth_type;
        $this->is_active = $site->is_active;

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function regenerateSecret(): void
    {
        $this->shared_secret = Str::random(32);
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['is_active'] = $this->is_active;
        $data['location'] = $this->location !== '' ? $this->location : null;
        $data['login_url'] = $this->login_url !== '' ? $this->login_url : null;
        $data['omada_auth_type'] = $this->omada_auth_type !== '' ? $this->omada_auth_type : null;

        $site = Site::updateOrCreate(['id' => $this->editingId], $data);
        $site->syncRadiusNasEntry();

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(int $siteId): void
    {
        Site::findOrFail($siteId)->delete();
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'location', 'radius_nas_ip', 'shared_secret', 'login_url', 'omada_auth_type']);
        $this->device_vendor = 'mikrotik';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.sites.index', [
            'sites' => Site::orderBy('name')->get(),
        ]);
    }
}
