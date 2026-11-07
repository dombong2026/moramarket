<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContentEntry extends Model
{
    protected $fillable = [
        'author_id','type','title','slug','excerpt','body_html','status',
        'seo_title','seo_description','featured_type','featured_media_id',
        'featured_youtube_id','featured_color','revision','published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function featuredMedia()
    {
        return $this->belongsTo(MediaAsset::class, 'featured_media_id');
    }

    public function revisions()
    {
        return $this->hasMany(ContentRevision::class);
    }

    public function scopePublic(Builder $q): Builder
    {
        return $q->where('status', 'published')->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
