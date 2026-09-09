<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadUserGroup extends Model
{
    protected $connection = 'radius';

    protected $table = 'radusergroup';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'username';

    protected $keyType = 'string';

    protected $fillable = [
        'username',
        'groupname',
        'priority',
    ];
}
