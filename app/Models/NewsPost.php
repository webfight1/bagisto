<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NewsPost extends Model
{
    protected $fillable = [
        'slug', 'title', 'excerpt', 'cover_image',
        'content_blocks', 'author', 'published_at', 'status',
    ];

    protected $casts = [
        'content_blocks' => 'array',
        'published_at'   => 'datetime',
        'status'         => 'boolean',
    ];

    /** Published, visible-now posts, ordered newest-first. */
    public function scopePublic(Builder $q): Builder
    {
        return $q->where('status', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');
    }
}
