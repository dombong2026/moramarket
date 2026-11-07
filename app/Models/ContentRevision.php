<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentRevision extends Model
{
    protected $fillable = ['content_entry_id','editor_id','revision','snapshot'];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }

    public function entry()
    {
        return $this->belongsTo(ContentEntry::class, 'content_entry_id');
    }
}
