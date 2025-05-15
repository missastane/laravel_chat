<?php

namespace App\Http\Services\Chat;

use App\Http\Services\File\FileService;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\Media;
use App\Models\Message;
use App\Models\MessageUserStatus;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MessageDeliveryService
{
    use ApiResponseTrait;
    public function createPrivateConversation(User $sender, User $receiver): Conversation
    {
        $conversationHash = generateConversationHash([$sender, $receiver]);
        $conversation = Conversation::createOrFirst([
            'conversation_hash' => $conversationHash,
            'is_group' => 2,
            'privacy_type' => 0
        ]);
        return $conversation;
    }

    public function checkSenderForPrivateReply(User $sender, Conversation $conversation)
    {
        $isSenderMember = ConversationUser::where('conversation_id', $conversation->id)
            ->where('user_id', $sender->id)
            ->whereNull('left_at')
            ->exists();

        $isGroupConversation = $conversation->groupConversation()->exists();
        $isAdmin = $sender->hasRole(['admin', 'superadmin']);

        if ($isGroupConversation && !$isSenderMember) {
            throw new HttpException(403, 'در مکالمه گروهی فقط اعضا می‌توانند پیام خصوصی ارسال کنند');
        }

        if (!$isGroupConversation && !$isSenderMember && !$isAdmin) {
            throw new HttpException(403, 'شما عضو این مکالمه نیستید');
        }
    }

    public function storeMessage(array $data): Message
    {
        $newMessage = Message::create($data);
        return $newMessage;
    }

    public function cloneMediaFromMessage(Message $source, Message $target)
    {
        if (!$source->relationLoaded('media')) {
            $source->load('media');
        }

        if ($source->media->isEmpty()) {
            return;
        }
        $mediaData = [];
        foreach ($source->media as $m) {
            $mediaData[] = [
                'message_id' => $target->id,
                'file_name' => $m->file_name,
                'file_path' => $m->file_path,
                'file_type' => $m->file_type,
                'file_size' => $m->file_size,
                'mime_type' => $m->mime_type,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        Media::insert($mediaData);
    }

    public function handleMediaUpload(Message $message, Conversation $conversation, Request $request, FileService $fileService)
    {
        if ($request->hasFile('media')) {

            $fileService->setExclusiveDirectory('messages' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $conversation->id);
            foreach ($request['media'] as $key => $file) {

                $fileService->setFileSize($file);
                $fileSize = $fileService->getFileSize();

                // upload file
                $upload = $fileService->moveToStorage($file, $file->getClientOriginalName());
                $fileFormat = $fileService->getFileFormat();
                $media = Media::create([
                    'message_id' => $message->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $upload,
                    'file_type' => $fileFormat,
                    'file_size' => $fileSize,
                    'mime_type' => $file->getMimeType(),
                ]);
            }
            $messageType = 0;
            if (!empty($inputs['content']) && $request->hasFile('media')) {
                $messageType = 1;
            } elseif (empty($inputs['content']) && $request->hasFile('media')) {
                $messageType = 2;
            }

            $message->update([
                'message_type' => $messageType
            ]);
        }
    }

    public function storeMessageStatusForUsers(Message $message, array $userIds): void
    {
        foreach ($userIds as $userId) {
            MessageUserStatus::create([
                'message_id' => $message->id,
                'user_id' => $userId,
                'status' => 0,
            ]);
        }
    }

    public function ensureUsersInConversation(Conversation $conversation, Message $message, array $userIds): void
    {
        foreach ($userIds as $id) {
            $conversationUser = ConversationUser::firstOrCreate(
                ['conversation_id' => $conversation->id, 'user_id' => $id],
                ['is_admin' => 0, 'status' => 1, 'joined_at' => now()]
            );
            $e = MessageUserStatus::create([
                'message_id' => $message->id,
                'user_id' => $id,
                'status' => 0, // sent
            ]);

        }
    }
}