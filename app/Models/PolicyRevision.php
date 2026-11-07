<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyRevision extends Model
{
    protected $fillable = ['policy_id','editor_id','revision','body_html'];
}
