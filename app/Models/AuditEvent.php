<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_id', 'action', 'subject_type', 'subject_id', 'changed_fields', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_fields'=>'array',
            'created_at'=>'datetime',
        ];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
