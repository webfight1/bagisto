<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OmnivaLocation extends Model
{
    protected $table = 'omniva_locations';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'raw' => 'array',
        'source_modified_at' => 'datetime',
    ];
}
