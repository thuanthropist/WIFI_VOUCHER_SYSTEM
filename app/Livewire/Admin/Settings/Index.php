<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Setting;
use App\Models\Site;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    #[Validate('required|string|max:255')]
    public string $app_name = '';

    #[Validate('nullable|string|max:32')]
    public string $support_phone = '';

    #[Validate('nullable|email|max:255')]
    public string $support_email = '';

    #[Validate('required|string|size:3')]
    public string $currency = 'TZS';

    #[Validate('required|integer|min:1|max:10')]
    public string $voucher_redeem_multiplier = '3';

    #[Validate('nullable|exists:sites,id')]
    public string $default_site_id = '';

    public function mount(): void
    {
        $defaults = Setting::all_defaults();

        foreach ($defaults as $key => $default) {
            $this->{$key} = (string) Setting::get($key, $default);
        }
    }

    public function save(): void
    {
        $data = $this->validate();

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        $this->dispatch('settings-saved');
    }

    public function render()
    {
        return view('livewire.admin.settings.index', [
            'sites' => Site::orderBy('name')->get(),
        ]);
    }
}
