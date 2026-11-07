<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\ContentEntry;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use App\Models\MediaAsset;
use App\Models\Order;
use App\Models\Policy;
use App\Models\Product;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\Vendor;
use App\Models\VendorShippingProfile;
use App\Services\AuditTrail;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Centralized change auditing. Do not duplicate audit calls in each
        // controller; model hooks cover updates from both UI and background jobs.
        $audited = [
            Category::class, ContentEntry::class, KnowledgeArticle::class,
            KnowledgeCategory::class, MediaAsset::class, Order::class,
            Policy::class, Product::class, SupportMessage::class,
            SupportTicket::class, Vendor::class, VendorShippingProfile::class,
        ];

        foreach ($audited as $model) {
            $model::created(static fn (Model $item) => AuditTrail::record('created', $item));
            $model::updated(static fn (Model $item) => AuditTrail::record('updated', $item));
            $model::deleted(static fn (Model $item) => AuditTrail::record('deleted', $item));
        }

        Event::listen(Login::class, static function (Login $event): void {
            AuditTrail::record('signed_in', $event->user);
        });
        Event::listen(Logout::class, static function (Logout $event): void {
            if ($event->user !== null) {
                AuditTrail::record('signed_out', $event->user);
            }
        });
    }
}
