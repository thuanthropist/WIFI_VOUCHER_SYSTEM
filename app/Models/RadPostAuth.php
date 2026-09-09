<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadPostAuth extends Model
{
    protected $connection = 'radius';

    protected $table = 'radpostauth';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'authdate' => 'datetime',
        ];
    }
}
