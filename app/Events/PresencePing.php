<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PresencePing implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $role; // 'student' or 'professor'
    public int $id;      // Stud_ID or Prof_ID
    public string $ts;   // ISO timestamp

    public function __construct(string $role, int $id)
    {
        $this->role = $role;
        $this->id = $id;
        $this->ts = now('Asia/Manila')->toIso8601String();
    }

    public function broadcastOn()
    {
        return new Channel('chat');
    }

    public function broadcastWith(): array
    {
        return [
            'role' => $this->role,
            'id' => $this->id,
            'ts' => $this->ts,
        ];
    }

    public function broadcastAs()
    {
        return 'PresencePing';
    }
}
