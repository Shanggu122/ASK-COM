<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingUpdatedStudent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $studId;
    public array $payload;

    public function __construct(int $studId, array $payload)
    {
        $this->studId = $studId;
        $this->payload = $payload;
    }

    public function broadcastOn(): Channel
    {
        return new Channel("bookings.stud." . $this->studId);
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }

    /**
     * Use a simple event name for client subscriptions.
     */
    public function broadcastAs(): string
    {
        return "BookingUpdated";
    }
}
