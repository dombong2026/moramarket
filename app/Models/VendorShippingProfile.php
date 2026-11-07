<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorShippingProfile extends Model
{
    protected $fillable=['vendor_id','enabled','flat_rate','free_above','estimated_days','policy_note'];

    protected function casts(): array
    {
        return ['enabled'=>'boolean','flat_rate'=>'decimal:2','free_above'=>'decimal:2','estimated_days'=>'integer'];
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function feeCents(int $subtotalCents): int
    {
        if (! $this->enabled) {
            return 0;
        }
        if ($this->free_above !== null && $subtotalCents >= (int) round((float)$this->free_above * 100)) {
            return 0;
        }
        return (int) round((float)$this->flat_rate * 100);
    }
}
