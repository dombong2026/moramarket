<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaAsset extends Model
{
    protected $fillable = [
        'user_id', 'path', 'original_name', 'alt_text', 'mime_type', 'size_bytes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'featured_media_id');
    }
}
