<?php

namespace App\Livewire\Admin\Vouchers;

use App\Jobs\GenerateVoucherJob;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Site;
use App\Models\Voucher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $siteId = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    // --- Manual voucher issuance ---
    public bool $showIssueModal = false;

    #[Validate('required|exists:plans,id')]
    public string $issuePlanId = '';

    #[Validate('nullable|exists:sites,id')]
    public string $issueSiteId = '';

    #[Validate('nullable|regex:/^(0|255)[67]\d{8}$/')]
    public string $issuePhone = '';

    #[Validate('required|integer|min:1|max:20')]
    public string $issueQuantity = '1';

    #[Validate('nullable|string|max:255')]
    public string $issueNote = '';

    /** @var array<int, string> */
    public array $issuedCodes = [];

    public function updating(string $property): void
    {
        if (in_array($property, ['status', 'siteId', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['status', 'siteId', 'from', 'to']);
    }

    public function openIssueModal(): void
    {
        $this->reset(['issuePlanId', 'issueSiteId', 'issuePhone', 'issueNote', 'issuedCodes']);
        $this->issueQuantity = '1';
        $this->resetErrorBag();
        $this->showIssueModal = true;
    }

    public function closeIssueModal(): void
    {
        $this->showIssueModal = false;
    }

    /**
     * Issues one or more vouchers directly (cash / courtesy) without a
     * gateway round-trip: each unit becomes a confirmed Payment(gateway
     * = manual) so it reports alongside online sales, then reuses the
     * same GenerateVoucherJob that payment confirmation dispatches.
     */
    public function issueVouchers(): void
    {
        $data = $this->validate();

        $plan = Plan::findOrFail($data['issuePlanId']);
        $site = $data['issueSiteId'] !== '' ? Site::find($data['issueSiteId']) : null;
        $phone = $data['issuePhone'] !== '' ? $data['issuePhone'] : '0000000000';

        $codes = [];

        for ($i = 0; $i < (int) $data['issueQuantity']; $i++) {
            $payment = Payment::create([
                'phone_number' => $phone,
                'plan_id' => $plan->id,
                'site_id' => $site?->id,
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'gateway' => 'manual',
                'issued_by' => Auth::id(),
                'gateway_reference' => (string) Str::uuid(),
                'status' => 'confirmed',
                'note' => $data['issueNote'] !== '' ? $data['issueNote'] : null,
                'paid_at' => now(),
            ]);

            GenerateVoucherJob::dispatchSync($payment);

            $codes[] = $payment->fresh('voucher')->voucher?->code;
        }

        $this->issuedCodes = array_filter($codes);
    }

    public function render()
    {
        $vouchers = Voucher::query()
            ->with(['plan', 'site', 'payment'])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->siteId !== '', fn ($q) => $q->where('site_id', $this->siteId))
            ->when($this->from !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->to))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.vouchers.index', [
            'vouchers' => $vouchers,
            'sites' => Site::orderBy('name')->get(),
            'plans' => Plan::where('is_active', true)->orderBy('price')->get(),
        ]);
    }
}
