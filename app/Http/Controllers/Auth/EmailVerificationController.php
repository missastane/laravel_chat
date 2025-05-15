<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    use ApiResponseTrait;
     /**
     * @OA\Post(
     *     path="/api/email/verification-notification", 
     *     summary="Resend email verification link", 
     *     description="Resends the email verification link to the user if their email is not already verified.", 
     *     operationId="resendVerificationEmail", 
     *     tags={"Authentication"}, 
     *     @OA\RequestBody( 
     *         required=true, 
     *         @OA\JsonContent( 
     *             required={"email"}, 
     *            @OA\Property(property="email", type="string", format="email", example="user@example.com") 
     *         ) 
     *     ),
     *     @OA\Response( 
     *         response=200, 
     *         description="Verification link sent successfully", 
     *         @OA\JsonContent( 
     *             @OA\Property(property="status", type="boolean", example=true), 
     *             @OA\Property(property="message", type="string", example="لینک تأیید ایمیل برای شما ارسال شد") 
     *         ) 
     *     ), 
     *     @OA\Response( 
     *         response=400, 
     *         description="Email already verified", 
     *         @OA\JsonContent( 
     *             @OA\Property(property="status", type="boolean", example=false), 
     *             @OA\Property(property="message", type="string", example="ایمیل شما قبلا تأیید شده است") 
     *         ) 
     *     ), 
     *     @OA\Response( 
     *         response=404, 
     *         description="User not found", 
     *         @OA\JsonContent( 
     *             @OA\Property(property="status", type="boolean", example=false), 
     *             @OA\Property(property="message", type="string", example="User not found") 
     *         ) 
     *     ) 
     * ) 
     */
    public function resendVerificationEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);
        $user = User::where('email', $request->email)->firstOrFail();

        if ($user->hasVerifiedEmail()) {
            return $this->error('ایمیل شما قبلا تأیید شده است',400);
        }
        $user->sendEmailVerificationNotification();
        return $this->success(null,'لینک تأیید ایمیل برای شما ارسال شد');
    }

    /**
     * @OA\Get(
     *     path="/api/email/verify", 
     *     summary="Check email verification status", 
     *     description="Checks whether the authenticated user's email is verified or not.", 
     *     operationId="checkVerificationStatus", 
     *     tags={"Authentication"}, 
     *     security={{ "bearerAuth":{} }}, 
     *     @OA\Response( 
     *         response=200, 
     *         description="Email is already verified", 
     *         @OA\JsonContent( 
     *             @OA\Property(property="status", type="boolean", example=true), 
     *             @OA\Property(property="message", type="string", example="ایمیل شما قبلا تأیید شده است") 
     *         ) 
     *     ), 
     *     @OA\Response( 
     *         response=403, 
     *         description="Email is not verified", 
     *         @OA\JsonContent( 
     *             @OA\Property(property="status", type="boolean", example=false), 
     *             @OA\Property(property="message", type="string", example="این ایمیل قبلا تأیید نشده است") 
     *         ) 
     *     ) 
     * ) 
     */
    public function checkVerificationStatus(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->success(null,'ایمیل شما قبلا تأیید شده است');
        }
        return $this->error('این ایمیل قبلا تأیید نشده است',403);
    }

    /**
     * @OA\Get(
     *     path="/api/email/verify/{id}/{hash}",
     *     summary="Verify user email",
     *     description="Verifies the user's email address using the ID and hash provided in the verification link.",
     *     operationId="verifyEmail",
     *     tags={"Authentication"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="hash",
     *         in="path",
     *         required=true,
     *         description="Hashed email token",
     *         @OA\Schema(type="string", example="c4ca4238a0b923820dcc509a6f75849b")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="ایمیل شما با موفقیت تأیید شد")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Email already verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="این ایمیل قبلا تأیید شده است")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Invalid verification link",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="تأیید ایمیل معتبر نیست")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);
        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return $this->error('تأیید ایمیل معتبر نیست',403);
        }
        if ($user->hasVerifiedEmail()) {
            return $this->error('این ایمیل قبلا تأیید شده است',400);
        }
        $user->markEmailAsVerified();
        event(new Verified($user));
        return $this->success(null,'ایمیل شما با موفقیت تأیید شد');
    }
}
