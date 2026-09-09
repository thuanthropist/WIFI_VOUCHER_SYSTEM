<?php

namespace App\Livewire\Admin\Plans;

use App\Models\Plan;
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

    #[Validate('required|numeric|min:0')]
    public string $price = '';

    #[Validate('required|string|size:3')]
    public string $currency = 'TZS';

    #[Validate('required|integer|min:1')]
    public string $duration_minutes = '';

    #[Validate('nullable|integer|min:1')]
    public string $speed_limit_mbps = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    public bool $is_active = true;

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $planId): void
    {
        $plan = Plan::findOrFail($planId);

        $this->editingId = $plan->id;
        $this->name = $plan->name;
        $this->price = (string) $plan->price;
        $this->currency = $plan->currency;
        $this->duration_minutes = (string) $plan->duration_minutes;
        $this->speed_limit_mbps = (string) $plan->speed_limit_mbps;
        $this->description = (string) $plan->description;
        $this->is_active = $plan->is_active;

        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['speed_limit_mbps'] = $this->speed_limit_mbps !== '' ? $this->speed_limit_mbps : null;
        $data['is_active'] = $this->is_active;

        Plan::updateOrCreate(['id' => $this->editingId], $data);

        $this->showModal = false;
        $this->resetForm();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $planId): void
    {
        $plan = Plan::findOrFail($planId);
        $plan->update(['is_active' => ! $plan->is_active]);
    }

    public function delete(int $planId): void
    {
        Plan::findOrFail($planId)->delete();
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'price', 'currency', 'duration_minutes', 'speed_limit_mbps', 'description']);
        $this->currency = 'TZS';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.plans.index', [
            'plans' => Plan::orderBy('price')->get(),
        ]);
    }
}
