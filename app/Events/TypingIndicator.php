<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TypingIndicator implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $stud_id;
    public $prof_id;
    public $sender;
    public $is_typing;

    public function __construct($studId, $profId, $sender, $isTyping)
    {
        $this->stud_id = $studId;
        $this->prof_id = $profId;
        $this->sender = $sender;
        $this->is_typing = (bool)$isTyping;
    }

    public function broadcastOn()
    {
        return new Channel('chat');
    }

    public function broadcastWith()
    {
        return [
            'stud_id' => $this->stud_id,
            'prof_id' => $this->prof_id,
            'sender' => $this->sender,
            'is_typing' => $this->is_typing,
        ];
    }

    public function broadcastAs()
    {
        return 'TypingIndicator';
    }
}
