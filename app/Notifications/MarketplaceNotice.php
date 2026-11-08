<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

final class MarketplaceNotice extends Notification
{
    public function __construct(
        public readonly string $heading,
        public readonly string $message,
        public readonly string $path,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // Never add personal data, SMTP credentials, or arbitrary user URLs.
        // The destination is always a trusted server-generated relative route.
        return [
            'heading' => $this->heading,
            'message' => $this->message,
            'path' => $this->path,
        ];
    }
}
