<?php

namespace App\Http\Middleware;

use App\Models\Conversation;
use App\Models\GroupConversation;
use App\Models\Media;
use App\Models\Message;
use Closure;
use Illuminate\Http\Request;
use Log;
use Symfony\Component\HttpFoundation\Response;

class CheckConversationNotBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */


    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $conversation = $this->resolveConversation($request);

      
        if (!$conversation instanceof Conversation) {
            return response()->json([
                'status' => false,
                'message' => 'مکالمه نامعتبر است',
            ], 400);
        }

        if ($conversation->isBlockedFor($user)) {
            return response()->json([
                'status' => false,
                'message' => 'شما به این مکالمه دسترسی ندارید',
            ], 403);
        }

        return $next($request);
    }

    protected function resolveConversation(Request $request): ?Conversation
    {
        $conversation = $request->route('conversation');

        if ($conversation instanceof Conversation) {
            return $conversation;
        }

        $message = $request->route('message');
        if ($message instanceof Message) {
            
            return $message->conversation()->first();
        }

        $media = $request->route('media');
        if ($media instanceof Media) {
            return $media->message->conversation;
        }

        $group = $request->route('groupConversation');
        if ($group instanceof GroupConversation) {
            return $group->conversation;
        }

        return null;
    }
}
