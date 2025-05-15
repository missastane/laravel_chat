<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Log;

class UserController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/admin/user",
     *     summary="List users with filters",
     *     description="Retrieve a paginated list of users with optional filters like role, block status, and search query (username, email, mobile).",
     *     tags={"Admin - User"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filter by user role name",
     *         required=false,
     *         @OA\Schema(type="string", example="admin")
     *     ),
     *     @OA\Parameter(
     *         name="is_blocked",
     *         in="query",
     *         description="Filter users by block status (true/false)",
     *         required=false,
     *         @OA\Schema(type="boolean", example="")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by username, email, or mobile",
     *         required=false,
     *         @OA\Schema(type="string", example="")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with paginated list of users",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="username", type="string", example="john_doe"),
     *                         @OA\Property(property="email", type="string", example="john@example.com"),
     *                         @OA\Property(property="mobile", type="string", example="09120000000"),
     *                         @OA\Property(property="is_blocked_value", type="boolean", example=false),
     *                         @OA\Property(property="roles", type="array",
     *                             @OA\Items(
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="name", type="string", example="admin")
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=100),
     *                 @OA\Property(property="last_page", type="integer", example=5)
     *             )
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
     *         response=403,
     *         description="Forbidden - You are not authorized as an admin",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="شما اجازه انجام این عملیات را ندارید")
     *     ))
     * )
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $request->role));
        }

        if ($request->has('is_blocked')) {
            $query->where('is_blocked', $request->boolean('is_blocked'));
        }

        
        if ($search = $request->search) {
            $mobile = normalizeMobile($request->search);
            $query->where(function ($q) use ($search,$mobile) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$mobile}%");
            });
        }

        $users = $query->select('id','username','email','mobile','is_blocked')->with('roles:id,name')->latest()->simplePaginate(20)
        ->each(function($user){
            $user->makeHidden(['activation_value','user_type_value','is_discoverable_value']);
            $user->roles->makeHidden('pivot');
        });
        return $this->success($users);
    }


    /**
     * @OA\Get(
     *     path="/api/admin/user/show/{user}",
     *     summary="Get user details",
     *     description="Retrieve the details of a specific user including their roles and permissions.",
     *     tags={"Admin - User"},
     *     security={{"bearerAuth":{}}},
     * 
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with user details",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object", ref="#/components/schemas/User"),
     *               
     *                 @OA\Property(property="roles", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="admin")
     *                     )
     *                 ),
     *                 @OA\Property(property="permissions", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=10),
     *                         @OA\Property(property="name", type="string", example="edit_users")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     * @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *  @OA\Response(
     *         response=403,
     *         description="Forbidden - You are not authorized as an admin",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="شما اجازه انجام این عملیات را ندارید")
     *     ))
     * )
     */
    public function show(User $user)
    {
        $user = $user->load(['roles:id,name', 'permissions:id,name']);
        $user->roles->makeHidden('pivot');
        $user->permissions->makeHidden('pivot');
        return $this->success($user);
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/user/block/{user}",
     *     summary="Block a user",
     *     description="Block a specific user by setting their 'is_blocked' field to true.",
     *     tags={"Admin - User"},
     *     security={{"bearerAuth":{}}},
     * 
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="User ID to be blocked",
     *         required=true,
     *         @OA\Schema(type="integer", example=7)
     *     ),
     * 
     *     @OA\Response(
     *         response=200,
     *         description="User successfully blocked",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="کاربر با موفقیت مسدود شد"),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     ),
     * @OA\Response(
     *         response=403,
     *         description="Forbidden - You are not authorized as an admin",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="شما اجازه انجام این عملیات را ندارید")
     *     )),
     *    @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     * @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *  @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *             @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *           )
     *     )
     * )
     */
    public function block(User $user)
    {
        try {
            $user->update(['is_blocked' => 1]);
            return $this->success(null, 'کاربر با موفقیت مسدود شد');
        } catch (Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/user/unblock/{user}",
     *     summary="Unblock a user",
     *     description="Unblock a specific user by updating their 'is_blocked' field to 2 (unblocked).",
     *     tags={"Admin - User"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="User ID to be unblocked",
     *         required=true,
     *         @OA\Schema(type="integer", example=7)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User successfully unblocked",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="کاربر با موفقیت از حالت بلاک خارج شد"),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     ),
     *  @OA\Response(
     *         response=403,
     *         description="Forbidden - You are not authorized as an admin",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="شما اجازه انجام این عملیات را ندارید")
     *     )),
     *  @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *    @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *             @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *           )
     *     )
     * )
     */
    public function unblock(User $user)
    {
        try {
            $user->update(['is_blocked' => 2]);
            return $this->success(null, 'کاربر با موفقیت از حالت بلاک خارج شد');
        } catch (Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/user/update-roles/{user}",
     *     summary="Update user roles",
     *     description="Sync a user's roles by providing an array of role IDs.",
     *     tags={"Admin - User"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=7)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"roles"},
     *             @OA\Property(
     *                 property="roles",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1),
     *                 description="Array of role IDs"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User roles updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="true"),
     *             @OA\Property(property="message", type="string", example="نقش‌ های کاربر بروزرسانی شد"),
     *             @OA\Property(property="data", type="string", example="null")
     *         )
     *     ),
     * @OA\Response(
     *         response=403,
     *         description="Forbidden - You are not authorized as an admin",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="شما اجازه انجام این عملیات را ندارید")
     *     )),
     *  @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     * @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="وارد کردن نقش الزامی است")
     *     )),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *             @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *           )
     *     )
     * )
     */
    public function updateRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'required|exists:roles,id',
        ]);
        try {
            $user->syncRoles($request->roles);
            return $this->success(null,'نقش‌ های کاربر بروزرسانی شد');
        } catch (Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/user/update-permissions/{user}",
     *     summary="Update user permissions",
     *     description="Sync a user's permissions by providing an array of permission IDs.",
     *     tags={"Admin - User"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=7)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permissions"},
     *             @OA\Property(
     *                 property="permissions",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1),
     *                 description="Array of permission IDs"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="User permissions updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="true"),
     *             @OA\Property(property="message", type="string", example="سطوح دسترسی کاربر بروزرسانی شد"),
     *             @OA\Property(property="data", type="string", example="null")
     *         )
     *     ),
     * @OA\Response(
     *         response=403,
     *         description="Forbidden - You are not authorized as an admin",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="شما اجازه انجام این عملیات را ندارید")
     *     )),
     *  @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     * @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="وارد کردن سطح دسترسی الزامی است")
     *     )),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *             @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *           )
     *     )
     * )
     */
    public function updatePermissions(Request $request, User $user)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'required|exists:permissions,id',
        ]);
        try {
            $user->syncPermissions($request->permissions);
            return $this->success(null,'سطوح دسترسی کاربر بروزرسانی شد');
        } catch (Exception $e) {
            return $this->error();
        }
    }


}


