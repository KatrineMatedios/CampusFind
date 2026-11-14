<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CampusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public ?string $url = null,
        public string $category = 'general',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'category' => $this->category,
        ];
    }
}
