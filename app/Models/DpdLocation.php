<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DpdLocation extends Model
{
    protected $table = 'dpd_locations';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'raw' => 'array',
        'opening_hours' => 'array',
        'source_modified_at' => 'datetime',
    ];
}
