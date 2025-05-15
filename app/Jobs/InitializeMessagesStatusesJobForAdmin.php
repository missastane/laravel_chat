<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\MessageUserStatus;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InitializeMessagesStatusesJobForAdmin implements ShouldQueue
{
    use Queueable;

    protected $conversation;
    protected $user;

    /**
     * Create a new job instance.
     */
    public function __construct(Conversation $conversation, User $user)
    {
        $this->conversation = $conversation;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->conversation->groupConversation) {
            $messageIds = $this->conversation->messages()->pluck('id')->toArray();
            $userId = $this->user->id;
            $data = [];
            foreach ($messageIds as $id) {
                $exists = MessageUserStatus::where('message_id', $id)
                    ->where('user_id', $userId)
                    ->exists();

                if (!$exists) {

                    $now = now();
                    $data[] = [
                        'message_id' => $id,
                        'user_id' => $userId,
                        'status' => 0,
                        'delivered_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            if (!empty($data)) {
                MessageUserStatus::insert($data);
            }
        }
    }
}
