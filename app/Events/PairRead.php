<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a viewer (student or professor) marks the counterpart's messages as read.
 * Mirrors common chat apps' read receipt semantics. We only care about the pair and the fact
 * that all counterpart messages up to (and including) last_read_message_id are now read.
 */
class PairRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $stud_id;
    public int $prof_id;
    public string $reader_role; // 'student' or 'professor' (the one who just read)
    public ?int $last_read_message_id; // highest message id of messages belonging to counterpart now read
    public string $ts; // ISO timestamp for potential ordering / debugging
    public ?string $last_created_at; // ISO of last counterpart message read (fallback ordering)

    public function __construct(int $studId, int $profId, string $readerRole, ?int $lastReadMessageId, ?string $lastCreatedAt)
    {
        $this->stud_id = $studId;
        $this->prof_id = $profId;
        $this->reader_role = $readerRole;
        $this->last_read_message_id = $lastReadMessageId;
        $this->last_created_at = $lastCreatedAt;
        $this->ts = now('Asia/Manila')->toIso8601String();
    }

    public function broadcastOn()
    {
        return new Channel('chat');
    }

    public function broadcastWith(): array
    {
        return [
            'stud_id' => $this->stud_id,
            'prof_id' => $this->prof_id,
            'reader_role' => $this->reader_role,
            'last_read_message_id' => $this->last_read_message_id,
            'ts' => $this->ts,
            'last_created_at' => $this->last_created_at,
        ];
    }

    public function broadcastAs()
    {
        return 'PairRead';
    }
}
