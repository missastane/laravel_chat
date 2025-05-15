<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConversationPolicy
{

    public function view(User $user, Conversation $conversation): bool
    {
        return !$conversation->isBlockedFor($user);
    }

    public function getMessages(User $user, Conversation $conversation)
    {
        return $user->hasRole(['admin', 'superadmin']) && $conversation->groupConversation()->exists();
    }

    public function send(User $user, Conversation $conversation): bool
    {
        if ($user->hasRole(['admin', 'superadmin']) && $conversation->groupConversation) {
            return false;
        }
        return $conversation->users()
            ->where('user_id', $user->id)
            ->wherePivot('left_at', null)
            ->exists();
    }

}
