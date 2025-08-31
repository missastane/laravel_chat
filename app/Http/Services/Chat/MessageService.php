<?php

namespace App\Http\Services\Chat;

use App\Events\NewMessage;
use App\Http\Requests\MessageReactionRequest;
use App\Http\Requests\MessageRequest;
use App\Http\Services\File\FileService;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\Media;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\MessageUserStatus;
use App\Models\PinnedMessage;
use App\Traits\ApiResponseTrait;
use App\Traits\GroupConversationTrait;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MessageService
{
    use ApiResponseTrait;

    use GroupConversationTrait;
    protected $messageDeliveryService;
    public function __construct(MessageDeliveryService $messageDeliveryService)
    {
        $this->messageDeliveryService = $messageDeliveryService;
    }
public function getMessages(Conversation $conversation)
{
    try {
        $user = auth()->user();

        // دریافت تعداد پیام در هر صفحه و شماره صفحه از query string
        $perPage = request('per_page', 5); // مقدار پیش‌فرض 10
        $page = request('page', 1);         // مقدار پیش‌فرض 1

        // واکشی پیام‌ها به ترتیب جدیدترین
        $messages = Message::where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->simplePaginate($perPage, ['*'], 'page', $page);

        // گرفتن اولین پیام خوانده‌نشده در همین صفحه
        $firstUnreadMessage = MessageUserStatus::where('user_id', $user->id)
            ->whereIn('message_id', $messages->pluck('id'))
            ->where('status', 1)
            ->orderBy('message_id', 'asc')
            ->first();

        // آپدیت پیام‌هایی که تحویل داده نشده بودن
        MessageUserStatus::where('user_id', $user->id)
            ->whereIn('message_id', $messages->pluck('id'))
            ->where('status', 0)
            ->update([
                'status' => 1, // delivered
                'delivered_at' => now()
            ]);

        return $this->success([
            'messages' => $messages,
            'first_unread_id' => $firstUnreadMessage?->message_id
        ]);

    } catch (Exception $e) {
        return $this->error($e->getMessage());
    }
}

    public function downloadMedia(Media $media)
    {
        try {
            return response()->download(storage_path('app' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . $media->file_path), $media->file_name);

        } catch (Exception $e) {
            return $this->error();
        }
    }
    public function sendMessage(Conversation $conversation, MessageRequest $request, FileService $fileService)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $sender = Auth::id();
            $conversationUsers = ConversationUser::where('conversation_id', $conversation->id)
                ->where('user_id', '!=', $sender)
                ->pluck('user_id')->toArray();
            $data = [
                'conversation_id' => $conversation->id,
                'sender_id' => $sender,
                'content' => $inputs['content'] ?? null,
                'message_type' => 0,
            ];
            $message = $this->messageDeliveryService->storeMessage($data);
            $this->messageDeliveryService->storeMessageStatusForUsers($message, $conversationUsers);
            $this->messageDeliveryService->handleMediaUpload($message, $conversation, $request, $fileService);
            $message->load('media');
            broadcast(new NewMessage($message))->toOthers();
            DB::commit();
            return $this->success(null, 'پیام شما با موفقیت ارسال شد');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }
    public function replyToMessage(Message $message, MessageRequest $request, FileService $fileService)
    {
        try {
            DB::beginTransaction();
            $inputs = $request->all();
            $sender = Auth::id();
            $conversationUsers = ConversationUser::where('conversation_id', $message->conversation->id)
                ->where('user_id', '!=', $sender)
                ->pluck('user_id')->toArray();
            $data = [
                'conversation_id' => $message->conversation->id,
                'sender_id' => $sender,
                'content' => $inputs['content'] ?? null,
                'message_type' => 0,
                'parent_id' => $message->id
            ];
            $newMessage = $this->messageDeliveryService->storeMessage($data);
            $this->messageDeliveryService->storeMessageStatusForUsers($newMessage, $conversationUsers);
            $this->messageDeliveryService->handleMediaUpload($newMessage, $message->conversation, $request, $fileService);
            $media = $newMessage->load('media');
            broadcast(new NewMessage($newMessage))->toOthers();
            DB::commit();
            return $this->success(null, 'پاسخ شما با موفقیت ارسال شد');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error();
        }
    }
    public function forwardMessage(Message $message, array $ConversationIds)
    {
        try {
            DB::beginTransaction();
            $sender = auth()->user();
            $userIds = [];
            foreach ($ConversationIds as $conversationId) {
                $conversation = Conversation::findOrFail($conversationId);
                $this->checkConversationMembership($conversation);
                $users = ConversationUser::where('conversation_id', $conversation->id)
                    ->where('user_id', '!=', $sender->id)
                    ->pluck('user_id')
                    ->toArray();

                $data = [
                    'conversation_id' => $conversation->id,
                    'sender_id' => $sender->id,
                    'content' => $message->content,
                    'message_type' => $message->message_type,
                    'forwarded_message_id' => $message->id, // برای ردیابی اینکه این پیام فوروارد شده از کیه
                ];
                $newMessage = $this->messageDeliveryService->storeMessage($data);
                // $media = $message->media;
                $this->messageDeliveryService->storeMessageStatusForUsers($newMessage, $userIds);
                $this->messageDeliveryService->cloneMediaFromMessage($message, $newMessage);
                $newMessage->load('media');
                broadcast(new NewMessage($newMessage))->toOthers();
            }
            DB::commit();
            return $this->success(null, 'پیام با موفقیت فوروارد شد');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }
    public function markAsRead(Message $message)
    {
        try {
            $userId = Auth::id();
            $status = MessageUserStatus::where('message_id', $message->id)
                ->where('user_id', $userId)
                ->first();

            if ($status && $status->status === 1) {
                $status->update([
                    'status' => 2, // read
                    'read_at' => now(),
                ]);
            }
            return $this->success(null, 'پیام به عنوان خوانده‌شده ثبت شد');
        } catch (Exception $e) {
            return $this->error();
        }
    }

    public function sendPrivateMessage(Message $message, MessageRequest $request, FileService $fileService)
    {
        try {
            DB::beginTransaction();
            $sender = auth()->user();
            $receiver = $message->sender;
            $userIds = [$sender->id, $receiver->id];
            $currentConversation = $message->conversation;
            $inputs = $request->all();
            if ($sender->id === $receiver->id) {
                return $this->error('شما نمیتوانید به خودتان پیام بفرستید', 403);
            }
            $this->messageDeliveryService->checkSenderForPrivateReply($sender, $currentConversation);
            $conversation = $this->messageDeliveryService->createPrivateConversation($sender, $receiver);
            $data = [
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'content' => $inputs['content'] ?? null,
                'message_type' => 0,
                'private_reply_message_id' => $message->id
            ];
            $newMessage = $this->messageDeliveryService->storeMessage($data);
            $this->messageDeliveryService->ensureUsersInConversation($conversation, $newMessage, $userIds);
            $this->messageDeliveryService->handleMediaUpload($newMessage, $conversation, $request, $fileService);
            $newMessage->load('media');
            broadcast(new NewMessage($newMessage))->toOthers();
            DB::commit();
            return $this->success(null, 'پیام خصوصی با موفقیت ارسال شد');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }

    }

    public function deleteMessage(Message $message)
    {
        try {
            DB::beginTransaction();
            $user = auth()->user();
            if ($message->sender_id !== $user->id) {
                return $this->error('شما نمی‌توانید این پیام را حذف کنید', 403);
            }
            MessageUserStatus::where('message_id', $message->id)->delete();
            foreach ($message->media as $media) {
                // if message was forwarede $doublicatedCount > 1;
                $duplicateCount = Media::where('file_path', $media->file_path)->count();

                if ($duplicateCount === 1) {

                    $fileService = new FileService;
                    $fileService->deleteFile('app' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . $media->file_path, true);
                }

                // delete media record
                $media->delete();
            }
            $message->delete();
            DB::commit();
            return $this->success(null, 'پیام با موفقیت حذف شد');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }
    public function deleteMessageForUser(Message $message)
    {
        try {
            $user = auth()->user();
            MessageUserStatus::where('message_id', $message->id)
                ->where('user_id', $user->id)
                ->delete();

            return $this->success(null, 'پیام برای شما حذف شد');

        } catch (Exception $e) {
            return $this->error();
        }
    }

    public function updateMessage(Message $message, MessageRequest $request)
    {
        try {
            $inputs = $request->all();
            $unreadCount = MessageUserStatus::where('message_id', $message->id)
                ->whereIn('status', [0, 1]) // 0 => sent,  1 => delivered
                ->count();

            if ($unreadCount == 0) {
                return $this->error('شما نمی‌توانید این پیام را ویرایش کنید چون توسط همه کاربران خوانده شده است', 400);
            }

            if ($message->sender_id == auth()->id()) {
                $message->update([
                    'content' => $inputs['content'],
                ]);
            } else {
                return $this->error('هر کاربر تنها میتواند پیام های ارسالی خودش را ویرایش کند', 403);
            }

            return $this->success(null, 'پیام شما با موفقیت ویرایش شد');
        } catch (Exception $e) {
            return $this->error();
        }
    }

    public function messageReactions(Message $message)
    {
        $reactions = MessageReaction::where('message_id', $message->id)
            ->with('user:id,username,first_name,last_name')
            ->get()
            ->each(function ($reaction) {
                $reaction->user?->makeHidden(['activation_value', 'user_type_value', 'is_discoverable_value']);
            })
            ->groupBy('emoji')
            ->map(function ($items, $emoji) {
                return [
                    'emoji' => $emoji,
                    'count' => $items->count(),
                    'users' => $items->pluck('user')->unique('id')->values()
                ];
            })
            ->values();

        return $this->success($reactions);
    }

    public function toggleReaction(MessageReactionRequest $request, Message $message)
    {
        try {
            $user = auth()->user();
            $emoji = $request->input('emoji');
            $reaction = MessageReaction::where('user_id', $user->id)
                ->where('message_id', $message->id)
                ->where('emoji', $emoji)
                ->first();

            if ($reaction) {
                $reaction->delete();
                return $this->success($emoji, 'ایموجی پاک شد');
            }
            MessageReaction::create([
                'user_id' => $user->id,
                'message_id' => $message->id,
                'emoji' => $emoji,
            ]);
            return $this->success($emoji, 'ایموجی به پیام افزوده شد');
        } catch (Exception $e) {
            return $this->error();
        }
    }

    public function pinnedMessage(Conversation $conversation)
    {
        $pins = $conversation->pinnedMessage->load('message');
        return $this->success($pins);
    }

    public function togglePin(Message $message)
    {
        try {
            $conversation = $message->conversation;
            $user = auth()->user();
            // if conversation is in a group and if user is a main admin
            $group = $conversation->groupConversation;
            $isGroup = !is_null($group);
            $isAdmin = $isGroup && $group->admin_user_id === $user->id;
            $isPublic = $isAdmin;

            // if already pinned, remove pin
            $existingPin = PinnedMessage::where('user_id', $user->id)
                ->where('conversation_id', $conversation->id)
                ->where('message_id', $message->id)
                ->first();

            if ($existingPin) {
                $existingPin->delete();
                return $this->success(null, 'پین پیام حذف شد');
            }

            // public pin allows only in groups 
            if ($isPublic) {
                PinnedMessage::where('conversation_id', $conversation->id)
                    ->where('is_public', true)
                    ->delete();
            }

            // create new pin
            PinnedMessage::create([
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'is_public' => $isPublic,
            ]);

            $message = $isPublic ? 'پیام به صورت عمومی پین شد' : 'پیام برای شما پین شد';
            return $this->success(null, $message, 201);

        } catch (Exception $e) {
            return $this->error('خطا در پین کردن پیام');
        }
    }

}