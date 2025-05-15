<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniquePhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // remove 0 | +98 from number
        $normalizedPhone = preg_replace('/^(?:\+98|0)/', '', $value);

        // check unique phoneNumber in db
        if (DB::table('users')->where('mobile', $normalizedPhone)->exists()) {
            $fail('شماره موبایل وارد شده قبلاً ثبت شده است.');
        }
    }
}
