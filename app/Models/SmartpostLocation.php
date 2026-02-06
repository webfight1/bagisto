<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmartpostLocation extends Model
{
    protected $table = 'smartpost_locations';

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
