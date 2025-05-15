<?php

namespace App\Rules;

use App\Models\GroupConversation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotInGroup implements ValidationRule
{
    protected GroupConversation $groupConversation;

    public function __construct(GroupConversation $groupConversation)
    {
        $this->groupConversation = $groupConversation;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isMember = $this->groupConversation
            ->conversation
            ->users()
            ->where('user_id', $value)
            ->exists();

        if ($isMember) {
            $fail("کاربر با شناسه {$value} قبلاً عضو گروه است.");
        }
    }
}
