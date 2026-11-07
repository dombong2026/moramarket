<?php

namespace App\Services;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

final class AuditTrail
{
    /**
     * Append-only audit metadata. NEVER capture field values, passwords,
     * addresses, payment information, support messages, or uploaded content.
     */
    public static function record(string $action, Model $subject): void
    {
        if (! Schema::hasTable('audit_events')) {
            return;
        }

        $fields = $action === 'updated'
            ? array_keys($subject->getChanges())
            : [];

        $fields = array_values(array_diff($fields, [
            'password', 'remember_token', 'customer_email', 'shipping_address',
            'body_html', 'body', 'payment_method', 'tracking_number',
        ]));

        AuditEvent::create([
            'actor_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => (int) $subject->getKey(),
            'changed_fields' => $fields,
            'created_at' => now(),
        ]);
    }
}
