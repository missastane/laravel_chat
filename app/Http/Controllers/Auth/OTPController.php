<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Services\OTP\OTPService;
use App\Models\Otp;
use Illuminate\Http\Request;

class OTPController extends Controller
{
    /**
     * @OA\Get(  
     *     path="/api/resend-otp/{token}", 
     *     summary="Resend OTP Code", 
     *     description="This endpoint is used to resend the verification code. The `OTP will only be resent if Token has not been used yet and was created more than two minutes ago`.",
     *     tags={"OTP"}, 
     *     security={{"bearerAuth":{}}}, 
     *     @OA\Parameter( 
     *         name="token", 
     *         in="path", 
     *         required=true, 
     *         @OA\Schema(type="string") 
     *     ), 
     *     @OA\Response( 
     *         response=200, 
     *         description="Resend OTP Code Successfully", 
     *         @OA\JsonContent( 
     *             @OA\Property(property="status", type="boolean", example="true"), 
     *             @OA\Property(property="message", type="string", example="کد تأیید مجدداً ارسال شد"), 
     *             @OA\Property(property="token", type="string", example="dbvfjb845ncknbvkm22ckjkhnck..."), 
     *             @OA\Property(property="meta", type="object", 
     *                 @OA\Property(property="next_step", type="string", example="redirect_to_/confirm_otp_page}") 
     *             ) 
     *         ) 
     *     ), 
     *     @OA\Response( 
     *         response=401, 
     *         description="Invalid Address",
     *         @OA\JsonContent( 
     *             @OA\Property(property="status", type="boolean", example="false"), 
     *             @OA\Property(property="message", type="string",example="آدرس وارد شده معتبر نیست"), 
     *             @OA\Property(property="meta", type="object", 
     *                 @OA\Property(property="next_step", type="string",example="redirect_back") 
     *             ) 
     *         ) 
     *     ) 
     * ) 
     */
    public function resendOtp($token, OTPService $oTPService)
    {
        $user = auth()->user();
        $otp = Otp::where('token', $token)->where('used', 0)->where('user_id', $user->id)->where('created_at', '<=', Carbon::now()->subMinute(2)->toDateTimeString())->first();
        if (empty($otp)) {
            return response()->json([
                'status' => false,
                'data' => [
                    'token' => $token
                ],
                'message' => 'آدرس وارد شده معتبر نیست',
                'meta' => [
                    'next_step' => 'redirect_back'
                ]

            ], 401);
        }
        
        // check mobile or email address
        // check id is email or not;
        $input = $otp->login_id;
        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $type = 1;  //id is an email
        } else {
            $type = 0; //id is a mobile number;
            $input = normalizeMobile($input);
        }
            
        // if otp was sent; don't resend it until expired
        $otp->checkOldOtp($input, $type);
        // create new otp record
        $newOtp = $oTPService->createOtp($input, $type, $user->id);

        // send sms or email

        if ($otp->type == 0) {
            // send sms
            $oTPService->sendSms($input,$newOtp->otp_code);

        } elseif ($otp->type == 1) {
            // send email
            $oTPService->sendEmail($input,$newOtp->otp_code);
        }
        return response()->json([
            'status' => true,
            'messsage' => 'کد تأیید مجددا ارسال شد',
            'data' => $token,
            'meta' => [
                    'next_step' => 'redirect_to_/confirm-otp_page'
                ]
        ], 200);
    }
}
