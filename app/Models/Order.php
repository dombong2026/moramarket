<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'number','user_id','vendor_id','status','payment_method',
        'payment_status','customer_name','customer_email','shipping_address',
        'subtotal','shipping_fee','total','shipping_carrier','tracking_number',
        'shipped_at','delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'=>'decimal:2','shipping_fee'=>'decimal:2','total'=>'decimal:2',
            'shipped_at'=>'datetime','delivered_at'=>'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
