<?php

namespace App\Events;

use App\Models\Professor;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProfessorUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Professor $professor;

    public function __construct(Professor $professor)
    {
        $this->professor = $professor;
    }

    public function broadcastOn(): Channel
    {
        return new Channel("professors.dept." . $this->professor->Dept_ID);
    }

    public function broadcastWith(): array
    {
        return [
            "Prof_ID" => $this->professor->Prof_ID,
            "Name" => $this->professor->Name,
            "Schedule" => $this->professor->Schedule,
            "Dept_ID" => $this->professor->Dept_ID,
            "profile_picture" => $this->professor->profile_picture,
            "profile_photo_url" => $this->professor->profile_photo_url,
        ];
    }
}
