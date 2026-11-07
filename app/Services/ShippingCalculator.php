<?php

namespace App\Services;

use App\Models\VendorShippingProfile;

class ShippingCalculator
{
    public function breakdown(array $groups): array
    {
        $result=[];
        foreach ($groups as $vendorId=>$lines) {
            $subtotalCents=0;
            foreach ($lines as [$product,$quantity]) {
                $subtotalCents += (int) round((float)$product->price * 100) * $quantity;
            }

            // A fresh vendor has a free-delivery default; this is always visible
            // in checkout. Vendors can explicitly configure a fee or pause delivery.
            $profile=VendorShippingProfile::where('vendor_id',$vendorId)->first();
            $enabled=$profile?->enabled ?? true;
            $feeCents=$profile?->feeCents($subtotalCents) ?? 0;

            $result[$vendorId]=[
                'subtotal_cents'=>$subtotalCents,
                'shipping_cents'=>$feeCents,
                'total_cents'=>$subtotalCents+$feeCents,
                'enabled'=>$enabled,
                'estimated_days'=>$profile?->estimated_days ?? 3,
            ];
        }
        return $result;
    }
}
