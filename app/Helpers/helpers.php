<?php
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

function generateConversationHash(array $users)
{
    //  شناسه‌های کاربران را مرتب می‌کنیم تا ترتیب‌ها مهم نباشند
    $userIds = collect($users)->sort()->pluck('id')->toArray();
    
    // تبدیل شناسه‌ها به یک رشته و هش کردن آن برای تولید conversation_hash
    return md5(implode('-', $userIds));
}


function normalizeMobile($input)
{
    if (preg_match('/^9\d{9}$/', $input)) {
        $input = '0' . $input;
    }
    if (preg_match('/^(\+98|98|0)9\d{9}$/', $input)) {
        // all mobile number are in one format 9** *** ****
        $input = ltrim($input, '0');
        $input = substr($input, 0, 2) === '98' ? substr($input, 2) : $input;
        $input = str_replace('+98', '', $input);
        return $input;
    } else {
        return false;
    }

}
function convertPersianToEnglish($number)
{
    // converts persian nums to english nums
    $number = str_replace('۰', '0', $number);
    $number = str_replace('۱', '1', $number);
    $number = str_replace('۲', '2', $number);
    $number = str_replace('۳', '3', $number);
    $number = str_replace('۴', '4', $number);
    $number = str_replace('۵', '5', $number);
    $number = str_replace('۶', '6', $number);
    $number = str_replace('۷', '7', $number);
    $number = str_replace('۸', '8', $number);
    $number = str_replace('۹', '9', $number);
    return $number;
}

function convertArabicToEnglish($number)
{
    $number = str_replace('۰', '0', $number);
    $number = str_replace('۱', '1', $number);
    $number = str_replace('۲', '2', $number);
    $number = str_replace('۳', '3', $number);
    $number = str_replace('۴', '4', $number);
    $number = str_replace('۵', '5', $number);
    $number = str_replace('۶', '6', $number);
    $number = str_replace('۷', '7', $number);
    $number = str_replace('۸', '8', $number);
    $number = str_replace('۹', '9', $number);
    return $number;
}

function convertEnglishToPersian($number)
{
    // converts persian nums to english nums
    $number = str_replace('0', '۰', $number);
    $number = str_replace('1', '۱', $number);
    $number = str_replace('2', '۲', $number);
    $number = str_replace('3', '۳', $number);
    $number = str_replace('4', '۴', $number);
    $number = str_replace('5', '۵', $number);
    $number = str_replace('6', '۶', $number);
    $number = str_replace('7', '۷', $number);
    $number = str_replace('8', '۸', $number);
    $number = str_replace('9', '۹', $number);
    return $number;
}

function priceFormat($price)
{
    $price = number_format($price, 0, '.', ',');
    $price = convertEnglishToPersian($price);
    return $price;
}

function validateNationalCode($nationalCode)
{
    $nationalCode = trim($nationalCode, ' .');
    $nationalCode = convertArabicToEnglish($nationalCode);
    $nationalCode = convertPersianToEnglish($nationalCode);
    $bannedArray = ['0000000000', '1111111111', '2222222222', '3333333333', '4444444444', '5555555555', '6666666666', '7777777777', '8888888888', '9999999999'];

    if (empty($nationalCode)) {
        return false;
    }
    // convert string to array and count number of members
    else if (count(str_split($nationalCode)) != 10) {
        return false;
    } else if (in_array($nationalCode, $bannedArray)) {
        return false;
    } else {
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $nationalCode[$i] * (10 - $i);
        }

        $divideRemaining = $sum % 11;
        if ($divideRemaining < 2) {
            $lastDigit = $divideRemaining;
        } else {
            $lastDigit = 11 - ($divideRemaining);
        }
        if ((int) $nationalCode[9] == $lastDigit) {
            return true;
        } else {
            return false;
        }
    }

}