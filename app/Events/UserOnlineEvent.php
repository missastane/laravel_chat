<?php

namespace App\Events;

use App\Models\User;
use Cache;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserOnlineEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $status; // 'online' or 'offline'
    /**
     * Create a new event instance.
     */
    public function __construct(User $user)
    {
        $this->userId = $user;
        $this->status = $this->checkUserOnlineStatus($user);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('online.users')
        ];
    }

    public function broadcastAs()
    {
        return 'user.status';
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->user->id,
            'status' => $this->status, // ارسال وضعیت آنلاین یا آفلاین
        ];
    }
    public function checkUserOnlineStatus(User $user)
    {
        // مثلا اگر شما از کش استفاده می‌کنید:
        return Cache::has('user-online-' . $user->id) ? 'online' : 'offline';
    }
}
