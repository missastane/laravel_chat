<?php 

namespace App\Http\Services\Chat;

use App\Events\UserTypingEvent;
use App\Http\Requests\ConversationRequest;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\Message;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Log;

class ConversationService
{
    use ApiResponseTrait;

    public function show(Conversation $conversation)
    {
        $userId = Auth::id();
        // if user is member of conversation
        broadcast(new UserTypingEvent($userId, $conversation->id));
        return $this->success($conversation);
    }


      public function store(User $targetUser)
    {
        try {
            DB::beginTransaction();
            $user = Auth::user();
            if ($user->id == $targetUser->id) {
                return $this->error('شما نمی‌توانید با خودتان مکالمه ایجاد کنید', 400);
            }
            $conversationHash = generateConversationHash([$user, $targetUser]);
            $existingConversation = Conversation::where('conversation_hash', $conversationHash)->first();
            if ($existingConversation) {
                return $this->error('مکالمه تکراری است', 400);
            }
            $conversation = Conversation::create([
                'conversation_hash' => $conversationHash,
                'is_group' => 2,
                'privacy_type' => 0
            ]);

            $userIds = [$user->id, $targetUser->id];
            foreach ($userIds as $id) {
                ConversationUser::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $id,
                    'is_admin' => 0,
                    'status' => 1,
                    'joined_at' => now(),
                ]);
            }
            DB::commit();
            return $this->success(null, 'مکالمه جدید ایجاد شد', 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error();
        }
    }

     public function search(Conversation $conversation, ConversationRequest $request)
    {
        $query = $request->search;
        $result = $conversation->messages()->whereNotNull('content')->where('content', 'like', '%' . $query . '%')->with('conversation:id', 'sender:id,first_name,last_name,username', 'parent:id,content')
            ->get()
            ->each(function ($message) {
                $message->sender->makeHidden(['activation_value', 'user_type_value', 'is_discoverable_value']);
                $message->conversation->makeHidden(['is_group_value', 'privacy_type_value']);
                if ($message->parent) {
                    $message->parent->makeHidden(['message_type_value']);
                }
            });
        return $this->success($result);
    }

     public function getLastSeenMessage(Conversation $conversation)
    {
        $user = auth()->user();

        $conversationUser = ConversationUser::where('conversation_id',$conversation->id)
            ->where('user_id', $user->id)
            ->first();
             $lastSeen = $conversationUser?->last_seen_message_id;
        return $this->success($lastSeen);
    }

    public function updateLastSeen(Conversation $conversation, Message $message)
    {
        try {
            $conversation->users()->updateExistingPivot(auth()->id(), [
                'last_seen_message_id' => $message->id
            ]);
            return $this->success(null, 'آخرین پیام دیده‌شده با موفقیت بروزرسانی شد');

        } catch (Exception $e) {
            return $this->error();
        }
    }
}