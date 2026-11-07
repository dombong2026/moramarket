<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class KnowledgeArticle extends Model
{
    protected $fillable=['category_id','author_id','title','slug','body_html','status','published_at'];

    protected function casts(): array
    {
        return ['published_at'=>'datetime'];
    }

    public function category()
    {
        return $this->belongsTo(KnowledgeCategory::class,'category_id');
    }

    public function scopePublic(Builder $q): Builder
    {
        return $q->where('status','published')->whereNotNull('published_at')
            ->where('published_at','<=',now());
    }
}
