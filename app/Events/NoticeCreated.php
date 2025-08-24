<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NoticeCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public array $data;

    public int $userId;

    public function __construct(array $data, int $userId)
    {

        $this->data   = $data;
        $this->userId = $userId;

    }

    public function broadcastOn(): Channel
    {
        return new Channel('notice.created.user.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'NoticeCreated';
    }

    public function broadcastWith(): array
    {
        return $this->data;
    }
}
