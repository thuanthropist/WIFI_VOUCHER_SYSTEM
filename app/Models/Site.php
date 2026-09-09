<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'radius_nas_ip',
        'shared_secret',
        'device_vendor',
        'login_url',
        'omada_auth_type',
        'nas_identifier',
        'is_active',
    ];

    protected $hidden = [
        'shared_secret',
    ];

    protected function casts(): array
    {
        return [
            'shared_secret' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * Provision (or refresh) the matching FreeRADIUS "nas" client row so
     * this site's router is recognised by the RADIUS server.
     */
    public function syncRadiusNasEntry(): void
    {
        Nas::updateOrCreate(
            ['nasname' => $this->radius_nas_ip],
            [
                'shortname' => Str::slug($this->name),
                'type' => 'other',
                'secret' => $this->shared_secret,
                'description' => $this->name,
            ]
        );
    }
}
