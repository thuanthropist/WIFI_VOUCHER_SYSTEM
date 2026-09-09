<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'plan_id',
        'site_id',
        'amount',
        'currency',
        'gateway',
        'issued_by',
        'gateway_reference',
        'gateway_transaction_id',
        'status',
        'meta',
        'note',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'meta' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function voucher(): HasOne
    {
        return $this->hasOne(Voucher::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function markConfirmed(?string $transactionId = null): void
    {
        $this->update([
            'status' => 'confirmed',
            'gateway_transaction_id' => $transactionId ?? $this->gateway_transaction_id,
            'paid_at' => now(),
        ]);
    }

    public function markFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
