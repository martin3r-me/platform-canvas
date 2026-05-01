<?php

namespace Platform\Canvas\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class WorkshopNoteChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $canvasId,
        public int $senderId,
        public string $action,
        public int $noteId,
        public array $data = [],
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("canvas.workshop.{$this->canvasId}")];
    }

    public function broadcastAs(): string
    {
        return 'note.changed';
    }
}
