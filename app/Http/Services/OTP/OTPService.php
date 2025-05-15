<?php

namespace App\Http\Services\OTP;

use App\Http\Services\Message\Email\EmailService;
use App\Http\Services\Message\MessageService;
use App\Http\Services\Message\SMS\SmsService;
use App\Models\Otp;
use DateInterval;
use DateTime;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
class OTPService
{
    public function createOtp($input, $type, $userId)
    {
        // create otp code
        $otpCode = rand(111111, 999999);
        $token = Str::random(60);
        $otpInputs = [
            'token' => $token,
            'user_id' => $userId,
            'otp_code' => $otpCode,
            'login_id' => $input,
            'type' => $type
        ];

        $newOtp = Otp::create($otpInputs);
        return $newOtp;
    }
    public function checkOldOtp($input, $type)
    {
        $oldOtp = Otp::where('login_id', $input)->where('used', 0)->orderBy('created_at', 'desc')->first();
        if ($oldOtp) {

            $minutes_to_add = 2;
            $expired = new DateTime($oldOtp->created_at);
            $expired->add(new DateInterval('PT' . $minutes_to_add . 'M'));
            $now = new DateTime();
            $timer = ((new \Carbon\Carbon($oldOtp->created_at))->addMinutes(2)->timestamp - \Carbon\Carbon::now()->timestamp);

            if ($now < $expired) {
                return response()->json([
                    'status' => false,
                    'message' => 'جهت ارسال مجدد کد تأیید لطفا '.$timer.' ثانیه دیگر منتظر بمانید',
                    'data' => [
                    'token' => $oldOtp->token,
                    'timer' => $timer
                    ]
                ],429);
            }

        }
    }

    public function sendEmail($input, $code)
    {
        $emailService = new EmailService();
        $details = [
            'title' => 'ایمیل فعال سازی حساب کاربری',
            'body' => "کد فعال سازی حساب کاربری شما : $code"
        ];
        $emailService->setDetails($details);
        $emailService->setFrom('noreply@example.com', 'amazon');
        $emailService->setSubject('کد احراز هویت');
        $emailService->setTo($input);
        $messageService = new MessageService($emailService);
        $messageService->send();
    }

    public function sendSms($input, $code)
    {
        $smsService = new SmsService();
        $smsService->setFrom(Config::get('sms.otp_from'));
        $smsService->setTo(['0' . $input]);
        $smsService->setText("مجموعه آمازون\n کد تأیید شما : $code");
        $smsService->setIsFlash(true);
        $messageService = new MessageService($smsService);
        $messageService->send();
    }

}