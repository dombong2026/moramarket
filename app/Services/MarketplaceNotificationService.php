<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\MarketplaceNotice;

final class MarketplaceNotificationService
{
    private function admins(MarketplaceNotice $notification): void
    {
        User::query()->where('role', 'admin')->chunkById(100, function ($admins) use ($notification): void {
            foreach ($admins as $admin) {
                $admin->notify($notification);
            }
        });
    }

    public function supportCreated(SupportTicket $ticket): void
    {
        $this->admins(new MarketplaceNotice(
            'New support ticket',
            'A customer opened a support conversation: '.$ticket->subject,
            route('admin.support.show', $ticket, false),
        ));
    }

    public function supportReplied(SupportTicket $ticket, bool $adminReplied): void
    {
        if ($adminReplied) {
            $ticket->user?->notify(new MarketplaceNotice(
                'Support replied',
                'You have a new reply on your support ticket: '.$ticket->subject,
                route('account.support.show', $ticket, false),
            ));

            return;
        }

        $this->admins(new MarketplaceNotice(
            'Customer replied',
            'A customer replied to a support conversation: '.$ticket->subject,
            route('admin.support.show', $ticket, false),
        ));
    }

    public function supportStatusChanged(SupportTicket $ticket): void
    {
        $ticket->user?->notify(new MarketplaceNotice(
            'Support ticket updated',
            'Your support ticket status is now '.ucfirst($ticket->status).'.',
            route('account.support.show', $ticket, false),
        ));
    }

    public function orderPlaced(Order $order): void
    {
        $order->vendor?->user?->notify(new MarketplaceNotice(
            'New store order',
            'A new order has been placed for your store. Review the order details.',
            route('vendor.orders', [], false),
        ));
    }

    public function orderUpdated(Order $order): void
    {
        $order->user?->notify(new MarketplaceNotice(
            'Order status updated',
            'Your order '.substr($order->number, 0, 8).' is now '.ucfirst($order->status).'.',
            route('account.orders', [], false),
        ));
    }
}
