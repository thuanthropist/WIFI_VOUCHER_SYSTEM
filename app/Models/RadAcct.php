<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadAcct extends Model
{
    protected $connection = 'radius';

    protected $table = 'radacct';

    protected $primaryKey = 'radacctid';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'acctstarttime' => 'datetime',
            'acctupdatetime' => 'datetime',
            'acctstoptime' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->whereNull('acctstoptime');
    }

    public function scopeForUsername($query, string $username)
    {
        return $query->where('username', $username);
    }
}
