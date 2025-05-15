<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    return $user->conversations()->where('conversations.id', $conversationId)->exists();
});

Broadcast::channel('presence-conversation.{id}', function ($user, $id) {
    return \App\Models\ConversationUser::where('conversation_id', $id)
        ->where('user_id', $user->id)
        ->exists()
        ? ['id' => $user->id, 'username' => $user->username]
        : false;
});

Broadcast::channel('online.users', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});