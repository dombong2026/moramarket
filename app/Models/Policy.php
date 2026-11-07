<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    protected $fillable = ['editor_id','title','slug','body_html','status','revision','published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('status','published')->whereNotNull('published_at')
            ->where('published_at','<=',now());
    }

    public function revisions()
    {
        return $this->hasMany(PolicyRevision::class);
    }
}
