<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @OA\Info(
 *     title="Laravel_Chat API",
 *     version="1.0.0",
 *     description="API برای چت گروهی و دونفره",
 *     @OA\Contact(
 *         email="missastaneh@gmail.com"
 *     ),
 *     @OA\License(
 *         name="Missastaneh",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * ),
 *  @OA\Components(
 *         @OA\SecurityScheme(
 *             securityScheme="bearerAuth",
 *             type="http",
 *             scheme="bearer"
 *         )
 *     )
 * )
 * @OA\Security(
 *     securityScheme="bearerAuth"
 * )
 */
class AuthController extends Controller
{
    use ApiResponseTrait;
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register a new user",
     *     description="This Method Registers a new User and Sends approval link to user to approve email",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username", "email", "password", "password_confirmation"},
     *             @OA\Property(property="username", type="string", example="exampleuser"),
     *             @OA\Property(property="email", type="string", format="email", example="example@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="r@mZ4Ob00r"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="r@mZ4Ob00r")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registeration was successfully and approval link sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="با تشکر از ثبت نام شما. لینک تأیید ایمیل به آدرس ایمیل وارد شده ارسال گردید. لطفا ابتدا ایمیل خود را تأیید فرمایید")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطایی غیرمنتظره در سرور رخ داده است. لطفا مجددا تلاش کنید"),
     *         )
     *     )
     * )
     */
    public function register(AuthRequest $request)
    {
        try {
            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $user->sendEmailVerificationNotification();

            return $this->success(null, 'با تشکر از ثبت نام شما. لینک تأیید ایمیل به آدرس ایمیل وارد شده ارسال گردید. لطفا ابتدا ایمیل خود را تأیید فرمایید', 201);
        } catch (\Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="User Login",
     *     description="Authenticate a user and return a JWT token if credentials are valid and email is verified",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="missastaneh@gmail.com"),
     *             @OA\Property(property="password", type="string", format="password", example="r@mZ4Ob00r")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(
     *         @OA\Property(property="status", type="boolean", example=true),
     *         @OA\Property(property="data", type="array", 
     *             @OA\Items(
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJK..."),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *       )
     *         ),
     *        @OA\Property(property="message", type="string", example=null)
     *      )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials or email not verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="ایمیل یا رمز عبور اشتباه است یا ایمیل شما تأیید نشده")
     *         )
     *     )
     * )
     */
    public function login(AuthRequest $request)
    {
        $user = User::where('email', $request->email)
            ->whereNotNull('email_verified_at')
            ->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('ایمیل یا رمز عبور اشتباه است یا ایمیل شما تأیید نشده', 401);
        }
        $token = JWTAuth::fromUser($user);
        return $this->success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="User Logout",
     *     description="Invalidate the current JWT token and log the user out",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful logout",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="کاربر با موفقیت از حساب کاربری خود خارج شد"),
     *             @OA\Property(property="data", type="string", example=null)
     *             
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing token",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function logout()
    {
        auth()->logout();
        return $this->success(null, 'کاربر با موفقیت از حساب کاربری خود خارج شد');
    }

    /**
     * @OA\Post(
     *     path="/api/refresh",
     *     summary="Refresh JWT Token",
     *     description="Refresh the expired JWT token and return a new token",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example=null),
     *             @OA\Property(property="data", type="string", example="new_jwt_token_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated- Invalid or expired token",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *         )
     *     ),
     *   @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید")
     *         )
     *     ),
     * )
     */
    public function refresh()
    {
        try {
            // JWTAuth::invalidate(JWTAuth::getToken());
            $newToken = auth()->refresh(true, true);
            return $this->success([
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return $this->error('توکن منقضی شده است', 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e) {
            return $this->error('توکن در لیست سیاه قرار گرفته است', 401);
        } catch (\Exception $e) {
            return $this->error();
        }
    }

}
