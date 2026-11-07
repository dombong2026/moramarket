<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeCategory extends Model
{
    protected $fillable=['name','slug'];

    public function articles()
    {
        return $this->hasMany(KnowledgeArticle::class,'category_id');
    }
}
