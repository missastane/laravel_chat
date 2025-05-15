<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Services\Image\ImageService;
use App\Http\Services\OTP\OTPService;
use App\Models\OTP;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Log;

class ProfileController extends Controller
{
    use ApiResponseTrait;
    /**
     * @OA\Get(
     *     path="/api/profile",
     *     summary="Retrieve Authenticated User Information",
     *     description="Retrieve `Authenticated User` Information",
     *     tags={"Profile"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Authenticated User Information",
     *         @OA\JsonContent(type="object",
     *         @OA\Property(property="status", type="boolean", example=true),
     *         @OA\Property(property="message", type="string", example=null),
     *         @OA\Property(property="data", type="object", 
     *             ref="#/components/schemas/User")
     *         )
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),  
     *  )
     */
    public function index()
    {
        $authUser = auth()->user();
        return $this->success($authUser);
    }

    /**
     * @OA\Put(
     *     path="/api/profile/update",
     *     summary="Update User's Personal Information",
     *     description="This method updates authenticated user's personal information",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_name"},
     *             @OA\Property(property="username", type="string", example="ali562"),
     *             @OA\Property(property="first_name", type="string", example="علی"),
     *             @OA\Property(property="last_name", type="string", example="رضایی"),
     *             @OA\Property(property="national_code", type="string", example="1234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile Has Updated Successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="حساب کاربری شما با موفقیت تغییر کرد"),
     *              @OA\Property(property="data", type="boolean", example=null)
     * )
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )), 
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطایی غیرمنتظره در سرور رخ داده است. لطفا دوباره تلاش کنید")
     *         )
     *     )
     * )
     */
    public function updateProfileInfo(ProfileUpdateRequest $request)
    {
        try {
            $data = $request->all();
            $user = auth()->user();
            $user->update($data);
            return $this->success('اطلاعات حساب کاربری شما با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Post( 
     *     path="/api/profile/update-contact", 
     *     summary="Send Otp to Update Mobile Or Email", 
     *     description="This method sends Otp code to change and confirm mobile or email and return token as data to redirect confirm contact", 
     *     tags={"Profile"}, 
     *     security={{"bearerAuth":{}}}, 
     *     @OA\RequestBody( 
     *         required=true, 
     *         @OA\JsonContent( 
     *             required={"id"}, 
     *             @OA\Property(property="id", type="string", example="example@example.com") 
     *         ) 
     *     ), 
     *     @OA\Response( 
     *         response=200, 
     *         description="OTP Send Successfully", 
     *         @OA\JsonContent( 
     *             type="object", 
     *             @OA\Property(property="status", type="boolean", example=true), 
     *             @OA\Property(property="message", type="string", example="جهت ویرایش موبایل یا ایمیل خود، لطفا کد تأیید ارسال‌شده را وارد کنید"), 
     *             @OA\Property(property="data", type="string", example="token_value") 
     *         ) 
     *     ), 
     *     @OA\Response( 
     *         response=422, 
     *         description="Invalid Input", 
     *         @OA\JsonContent( 
     *             type="object", 
     *             @OA\Property(property="status", type="boolean", example=false), 
     *             @OA\Property(property="message", type="string", example="ایمیل یا شماره موبایل باید منحصربفرد باشد") 
     *         ) 
     *     ),
     *    @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )), 
     *   @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطایی غیرمنتظره در سرور رخ داده است. لطفا دوباره تلاش کنید")
     *         )
     *     )
     * ) 
     */
    public function updateProfileContact(ProfileUpdateRequest $request, OTPService $oTPService)
    {
        try {
            $inputs = $request->all();
            $oldUser = User::where('email', $inputs['id'])->orWhere('mobile', ltrim(preg_replace('/^(\+98|0)/', '', $inputs['id'])))->first();
            if (!empty($oldUser)) {
                return $this->error('ایمیل یا شماره موبایل باید منحصربفرد باشد', 422);
            }
            if (filter_var($inputs['id'], FILTER_VALIDATE_EMAIL)) {
                $type = 1;  //id is an email
            } elseif (normalizeMobile($inputs['id'])) {
                $type = 0; //id is a mobile number;
            } else {
                return $this->error('شناسه ورودی شما ایمیل یا شماره موبایل نیست', 422);
            }
            $user = auth()->user();
            $otp = $oTPService->createOtp($inputs['id'], $type, $user->id);
            if ($type == 0) {
                $oTPService->sendSms($inputs['id'], $otp->otp_code);
            } elseif ($type == 1) {
                $oTPService->sendEmail($inputs['id'], $otp->otp_code);
            }
            return $this->success([
                $otp->token,
                'meta' => [
                    'next_step' => 'redirect_to_/confirm_otp'
                ]
            ], 'جهت ویرایش موبایل یا ایمیل خود با وارد کردن کد تأیید 6 رقمی ارسال شده لطفا آن را تأیید نمایید');

        } catch (\Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Put(
     *     path="/api/profile/confirm-contact/{token}",
     *     summary="Confirm OTP to change Mobile or Email",
     *     description="This method Update Mobile or Email if OTP code is valid",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         description="token from otp record",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"otp"},
     *             @OA\Property(property="otp", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email or Mobile is Updated Successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="اطلاعات حساب کاربری شما با موفقیت تغییر کرد")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="OTP is Invalid",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="کد وارد شده معتبر نیست")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal servr error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطایی غیرمنتظره در سرور رخ داده است. لطفا دوباره تلاش کنید")
     *         )
     *     )
     * )
     */
    public function profileCantactConfirm($token, Request $request)
    {
        try {
            $inputs = $request->all();
            $user = auth()->user();
            $otp = OTP::where('token', $token)->where('user_id', $user->id)->where('used', 0)->where('created_at', '>=', Carbon::now()->subMinute(2)->toDateTimeString())->first();
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
            // if otp code missmatch:
            if ($otp->otp_code !== $inputs['otp']) {
                return response()->json([
                    'status' => false,
                    'data' => [
                        'token' => $token
                    ],
                    'message' => 'کد وارد شده معتبر نیست',
                    'meta' => [
                        'next_step' => 'redirect_back'
                    ]

                ], 401);
            }
            // if everything is ok:
            $otp->update(['used' => 1]);
            if ($otp->type == 0) {
                $user->update(['mobile_verified_at' => Carbon::now(), 'mobile' => normalizeMobile($otp->login_id)]);
            } elseif ($otp->type == 1) {
                $user->update(['email_verified_at' => Carbon::now(), 'email' => $otp->login_id]);
            }
            return $this->success(null, 'اطلاعات حساب کاربری شما با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Post(
     *     path="/api/profile/update-avatar",
     *     summary="Update user's profile avatar",
     *     description="This endpoint is used to update the user's profile picture.",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="New profile avatar image",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"profile_photo_path"},
     *                 @OA\Property(
     *                     property="profile_photo_path",
     *                     type="string",
     *                     format="binary",
     *                     description="New profile avatar image"
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="The profile avatar has been successfully updated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="آواتار پروفایل شما با موفقیت تغییر کرد")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="If the image upload fails.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="بارگذاری تصویر با خطا مواجه شد")
     *         )
     *     ),
     *    @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )), 
     *   @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطایی غیرمنتظره در سرور رخ داده است. لطفا دوباره تلاش کنید")
     *         )
     *     )
     * )
     */
    public function updateProfileAvatar(Request $request, ImageService $imageService)
    {
        try {
            $inputs = $request->all();
            $user = auth()->user();
            if ($request->hasFile('profile_photo_path')) {
                if (!empty($user->profile_photo_path)) {
                    $imageService->deleteImage($user->profile_photo_path);
                }
                $imageService->setExclusiveDirectory('images' . DIRECTORY_SEPARATOR . 'user');
                $result = $imageService->save($request->file('profile_photo_path'));
                if ($result === false) {
                    return $this->error('بارگذاری عکس با خطا مواجه شد', 422);
                }
                $inputs['profile_photo_path'] = $result;
                $user->update([
                    'profile_photo_path' => $result
                ]);
                return $this->success(null, 'آواتار پروفایل شما با موفقیت تغییر کرد');
            }
        } catch (\Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/profile/change-password",
     *     tags={"Profile"},
     *     summary="Change user's password",
     *     description="This endpoint is used to change the authenticated user's current password.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="The current and new passwords for updating",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"current_password", "new_password"},
     *                 @OA\Property(
     *                     property="current_password",
     *                     type="string",
     *                     description="Current password of the user"
     *                 ),
     *                 @OA\Property(
     *                     property="new_password",
     *                     type="string",
     *                     description="New password for the user"
     *                 ),
     *                 @OA\Property(
     *                     property="new_password_confirmation",
     *                     type="string",
     *                     description="Repeated New password for the user"
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="The password has been successfully changed.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="کلمه عبور با موفقیت تغییر کرد")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="If the current password is incorrect.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="کلمه عبور صحیح نیست")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )), 
     *   @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطایی غیرمنتظره در سرور رخ داده است. لطفا دوباره تلاش کنید")
     *         )
     *     )
     * )
     */
    public function changePassword(AuthRequest $request)
    {
        try {
            $user = auth()->user();
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->error('کلمه عبور صحیح نیست', 400);
            }
            $user->update(['password' => Hash::make($request->new_password)]);
            return $this->success(null, 'کلمه عبور با موفقیت تغییر کرد');
        } catch (Exception $e) {
            return $this->error();
        }
    }

    /**
     * Toggle user's discoverability status (public/private).
     *
     * @OA\Patch(
     *     path="/api/profile/toggle-discoverable",
     *     summary="Toggle user's discoverable status",
     *     description="This endpoint allows the authenticated user to toggle their discoverability status between public and hidden. If the user is discoverable, they will be shown in search and suggestions.",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Status toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="شما از جسنجوها مخفی شدید"),
     *             @OA\Property(property="data", type="string", example=null)
     * )
     *     ),
     *      @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *  @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید")
     *         )
     *     )
     * )
     */
    public function toggleDiscoverable(Request $request)
    {
        try {
            $user = auth()->user();
            $user->update([
                'is_discoverable' => $user->is_discoverable == 2 ? 1 : 2
            ]);
            $message = $user->is_discoverable == 2 ? 'شما از جستجوها مخفی شدید' : 'شما اکنون در جستجوها قابل مشاهده هستید';
            return $this->success(null, $message);
        } catch (Exception $e) {
            return $this->error();
        }
    }
}
