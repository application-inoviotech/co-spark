<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/** Live alert for staff signed in to the admin panel (private-admin, event "alert"). */
class AdminAlert implements ShouldBroadcastNow
{
    public function __construct(
        public string $title,
        public string $summary,
        public ?string $url = null,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('admin');
    }

    public function broadcastAs(): string
    {
        return 'alert';
    }
}
