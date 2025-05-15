<?php 

namespace App\Traits;

use App\Exceptions\UnauthorizedConversationActionException;
use App\Models\Conversation;
use App\Models\GroupConversation;
use App\Models\Message;
use Log;

trait GroupConversationTrait
{
    public function checkConversationMembership(Conversation $conversation)
    {
        $isMember = $conversation->users()->where('user_id', auth()->id())->where('left_at',null)->exists();
        if (!$isMember) {
             throw new UnauthorizedConversationActionException('شما عضو این مکالمه نیستید');
        }
        return true;
    }

    public function checkSystemAdminCan(Message $message)
    {
        $isGroup = $message->conversation->groupConversation()->exists();
        if($isGroup && auth()->user()->hasRole(['admin','superadmin'])){
               throw new UnauthorizedConversationActionException('شما عضو این مکالمه نیستید');
        }
        return true;
    }

    public function checkGroupAdmin(Conversation $conversation)
    {
        $isAdmin = $conversation->users()
            ->where('user_id', auth()->id())
            ->wherePivot('is_admin', 1)->wherePivot('left_at',null)
            ->exists();

        if (!$isAdmin) {
            throw new UnauthorizedConversationActionException('شما مجوز انجام این عملیات را ندارید');
        }

        return true;
    }

    public function checkMainAdmin(GroupConversation $groupConversation)
    {
        if ($groupConversation->admin_user_id !== auth()->id()) {
            throw new UnauthorizedConversationActionException('شما مجوز انجام این عملیات را ندارید');
        }

        return true;
    }
}