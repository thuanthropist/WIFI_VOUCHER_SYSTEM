<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadGroupReply extends Model
{
    protected $connection = 'radius';

    protected $table = 'radgroupreply';

    public $timestamps = false;

    protected $fillable = [
        'groupname',
        'attribute',
        'op',
        'value',
    ];
}
