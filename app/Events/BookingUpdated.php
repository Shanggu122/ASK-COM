<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The professor ID to target the channel.
     */
    public int $profId;

    /**
     * Payload to broadcast to clients.
     */
    public array $payload;

    /**
     * Create a new event instance.
     */
    public function __construct(int $profId, array $payload)
    {
        $this->profId = $profId;
        $this->payload = $payload;
    }

    public function broadcastOn(): Channel
    {
        return new Channel("bookings.prof." . $this->profId);
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }

    /**
     * Broadcast the event with a simple name so clients can bind to 'BookingUpdated'.
     */
    public function broadcastAs(): string
    {
        return "BookingUpdated";
    }
}
