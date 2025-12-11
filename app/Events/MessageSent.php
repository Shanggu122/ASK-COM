<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $stud_id;
    public $prof_id;
    public $sender;
    public $file;
    public $file_type;
    public $original_name;
    public $created_at_iso;
    public $client_uuid; // used for frontend optimistic dedupe

    /**
     * Create a new event instance.
     */
    public function __construct(array $payload)
    {
        $this->message = $payload['message'] ?? '';
        $this->stud_id = $payload['stud_id'] ?? null;
        $this->prof_id = $payload['prof_id'] ?? null;
        $this->sender = $payload['sender'] ?? null;
        $this->file = $payload['file'] ?? null;
        $this->file_type = $payload['file_type'] ?? null;
        $this->original_name = $payload['original_name'] ?? null;
        $this->created_at_iso = $payload['created_at_iso'] ?? now('Asia/Manila')->toIso8601String();
        $this->client_uuid = $payload['client_uuid'] ?? null;
        if(config('app.debug')){
            Log::debug('MessageSent event constructed', [
                'stud_id'=>$this->stud_id,
                'prof_id'=>$this->prof_id,
                'sender'=>$this->sender,
                'file'=>$this->file,
                'client_uuid'=>$this->client_uuid,
            ]);
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel
     */
    public function broadcastOn()
    {
        return new Channel('chat');
    }

    public function broadcastWith()
    {
        $payload = [
            'message' => $this->message,
            'stud_id' => $this->stud_id,
            'prof_id' => $this->prof_id,
            'sender' => $this->sender,
            'file' => $this->file,
            'file_type' => $this->file_type,
            'original_name' => $this->original_name,
            'created_at_iso' => $this->created_at_iso,
            'client_uuid' => $this->client_uuid,
        ];
    if(config('app.debug')){ Log::debug('MessageSent broadcast payload', $payload); }
        return $payload;
    }

    public function broadcastAs()
    {
        return 'MessageSent';
    }
}
