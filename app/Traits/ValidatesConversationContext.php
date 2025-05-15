<?php

namespace App\Traits;

use App\Exceptions\UnauthorizedConversationActionException;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

trait ValidatesConversationContext
{
    protected function validateUserAndMessageInConversation(?Conversation $conversation = null, ?Message $message = null)
    {
        $user = Auth::user();

        if ($message && !$conversation) {
            $conversation = $message->conversation;
        }

        if ($message && $conversation && $message->conversation_id !== $conversation->id) {
            throw new UnauthorizedConversationActionException('پیام مورد نظر متعلق به این مکالمه نیست');

        }

        if ($conversation && !$conversation->users()->where('user_id', $user->id)->exists()) {
            throw new UnauthorizedConversationActionException('شما عضو این مکالمه نیستید');

        }
    }
}
