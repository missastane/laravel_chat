<?php

namespace App\Rules;

use App\Models\Conversation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ActiveConversationMember implements ValidationRule
{
    public function __construct(private Conversation $conversation) {}
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isMember = $this->conversation->users()
            ->where('user_id', $value)
            ->whereNull('conversation_user.left_at')
            ->exists();

        if (!$isMember) {
            $fail('این کاربر عضو فعال گروه نیست و نمی‌تواند مالک شود');
        }
    }
}
