<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class MessagePolicy
{
    public function markAsRead(User $user, Message $message): bool
    {
        $conversation = $message->conversation;
        return $conversation->users()
            ->where('user_id', $user->id)
            ->wherePivot('left_at', null)
            ->exists();
    }

    public function forward(User $user, Message $message): bool
    {
        if ($user->hasRole(['admin', 'superadmin'])) {
            // admins only can forward to messages from non group conversation
            return !$message->conversation()->groupConversation()->exists();
        }

        // users can if they are the members of the current conversation
        return DB::table('conversation_user')
            ->where('conversation_id', $message->conversation->id)
            ->where('user_id', $user->id)
            ->exists();
    }


    public function reply(User $user, Message $message): bool
    {
        if ($user->hasRole(['admin', 'superadmin'])) {
            // admins only can reply to messages from non group conversation
            return !$message->conversation()->groupConversation()->exists();
        }

        // users can if they are the members of the current conversation
        return DB::table('conversation_user')
            ->where('conversation_id', $message->conversation->id)
            ->where('user_id', $user->id)
            ->exists();
    }


    public function sendPrivateMessage(User $user, Message $message): bool
    {
        $conversation = $message->conversation;
        $isGroup = $conversation->groupConversation;
        $isAdmin = $user->hasRole(['admin', 'superadmin']);

        $isMember = DB::table('conversation_user')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->exists();

        // if user is admin and conversation is group no
        if ($isGroup && $isAdmin) {
            return false;
        }

        // if user is member yes
        if ($isMember) {
            return true;
        }

        // not group and is admin yes
        if (!$isGroup && $isAdmin) {
            return true;
        }

        // otherwise no
        return false;
    }




}
