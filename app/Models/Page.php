<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'slug', 'title', 'data', 'content_blocks', 'excerpt', 'status',
        'in_menu', 'menu_position', 'menu_label',
    ];

    protected $casts = [
        'data'           => 'array',
        'content_blocks' => 'array',
        'status'         => 'boolean',
        'in_menu'        => 'boolean',
        'menu_position'  => 'integer',
    ];
}
