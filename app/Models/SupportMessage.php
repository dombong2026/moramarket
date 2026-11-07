<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    protected $fillable=['support_ticket_id','user_id','body'];

    public function author()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
