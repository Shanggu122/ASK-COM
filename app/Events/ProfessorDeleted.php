<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProfessorDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $profId;
    public int $deptId;

    public function __construct(int $profId, int $deptId)
    {
        $this->profId = $profId;
        $this->deptId = $deptId;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('professors.dept.' . $this->deptId);
    }

    public function broadcastWith(): array
    {
        return [
            'Prof_ID' => $this->profId,
            'Dept_ID' => $this->deptId,
        ];
    }
}
