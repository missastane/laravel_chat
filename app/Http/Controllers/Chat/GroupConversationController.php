<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\GroupConversationRequest;
use App\Http\Services\Image\ImageService;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\GroupConversation;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\GroupConversationTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;

class GroupConversationController extends Controller
{
    use ApiResponseTrait, GroupConversationTrait;
    /**
     * @OA\Get(
     *     path="/api/conversation/group/get-members/{conversation}",
     *     summary="Get all active members of a group conversation",
     *     description="Returns a list of users who are currently members of a specific group conversation (not left the group). Only works for group conversations.",
     *     operationId="getGroupMembers",
     *     tags={"Group Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="ID of the conversation",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of active group members",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example=null),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="username", type="string", example="Ali Ghasemi"),
     *                     @OA\Property(property="pivot", type="object",
     *                         @OA\Property(property="is_admin", type="integer", example=2)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     * @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *   @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *     @OA\Response(
     *         response=400,
     *         description="The conversation is not a group chat",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="این مکالمه گروهی نیست"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     )
     * )
     */
    public function members(Conversation $conversation)
    {
        $isGroup = $conversation->is_group == 1;
        if (!$isGroup) {
            return $this->error('این مکالمه گروهی نیست', 400);
        }
        $members = $conversation->users()->whereNull('left_at')->select('users.id', 'users.username')
            ->withPivot('is_admin')
            ->get()
            ->makeHidden([
                'activation_value', 
                'user_type_value', 
                'is_discoverable_value'
            ])
            ->each(function ($user) {
                unset($user->pivot->user_id, $user->pivot->conversation_id);
    });
        return $this->success($members, null);
    }

    /**
     * @OA\Delete(
     *     path="/api/conversation/group/{conversation}/remove-member/{user}",
     *     summary="Remove a member from a group conversation",
     *     description="Allows a group admin to remove a specific member from the group conversation. The admin cannot remove themselves.",
     *     operationId="removeGroupMember",
     *     tags={"Group Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="ID of the conversation",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID of the user to be removed from the group",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member removed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="کاربر با موفقیت از گروه چت حذف شد"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Not allowed to remove this user or not a group conversation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما نمی‌توانید خودتان را از گروه حذف کنید"),
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
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *  @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید")
     *         )
     *     ),
     * )
     */
    public function removeMember(Conversation $conversation, User $user)
    {
        $this->checkGroupAdmin($conversation);
        try {
            $isGroup = $conversation->is_group == 1;
            $isMember = $conversation->users()->where('user_id', $user->id)->exists();
            $authUser = auth()->user();

            if (!$isGroup || !$isMember) {
                return response()->json([
                    'status' => false,
                    'message' => 'کاربر مورد نظر عضو این مکالمه نیست یا این مکالمه گروهی نیست'
                ], 403);
            }

            if ($authUser->id === $user->id) {
                return $this->error('شما نمی‌توانید خودتان را از گروه حذف کنید', 403);
            }

            $conversation->users()->updateExistingPivot($user->id, [
                'deleted_at' => now(),
            ]);

            return $this->success(null, 'کاربر با موفقیت از گروه چت حذف شد');
        } catch (Exception $e) {
            return $this->error();
        }
    }


    /**
     * @OA\Patch(
     *     path="/api/conversation/group/{conversation}/update-member-role/{user}",
     *     summary="Update a group member's role",
     *     description="Allows the main admin to update the role of a group member (admin or regular user).",
     *     operationId="updateMemberRole",
     *     tags={"Group Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="ID of the conversation (group)",
     *         @OA\Schema(type="integer", example=8)
     *     ),
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID of the member whose role is being updated",
     *         @OA\Schema(type="integer", example=17)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"is_admin"},
     *             @OA\Property(
     *                 property="is_admin",
     *                 type="integer",
     *                 enum={1, 2},
     *                 description="Role to assign (1 for admin, 2 for regular member)",
     *                 example=1
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member role updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="کاربر به ادمین تبدیل شد"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Only the main admin can update member roles",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما مجوز انجام این عملیات را ندارید"),
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
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *    @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید")
     *         )
     *     ),
     * )
     */
    function updateMemberRole(Conversation $conversation, User $user, GroupConversationRequest $request)
    {
        $this->checkMainAdmin($conversation->groupConversation);
        try {
            $authUser = auth()->user();
            $newRole = $request->input('is_admin'); // 1:admin, 2: user
            $conversation->users()->updateExistingPivot($user->id, [
                'is_admin' => $newRole
            ]);
            $message = $newRole == 1 ? 'کاربر به ادمین تبدیل شد' : 'نقش ادمینی از کاربر گرفته شد';
            return $this->success(null, $message);
        } catch (Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Get(
     *     path="/api/conversation/group/show/{groupConversation}",
     *     summary="Show a specific group conversation",
     *     description="Returns details of a specific group conversation if the authenticated user is a member.",
     *     operationId="showGroupConversation",
     *     tags={"Group Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="groupConversation",
     *         in="path",
     *         required=true,
     *         description="ID of the group conversation",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Group conversation details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", nullable=true, example=null),
     *             @OA\Property(property="data", ref="#/components/schemas/GroupConversation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="User is not a member of the group conversation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما عضو این مکالمه نیستید"),
     *         )
     *     ),
     *      @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *   @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     * )
     */
    public function showGroup(GroupConversation $groupConversation)
    {
        $authUser = auth()->user();
        $this->checkConversationMembership($groupConversation->conversation);
        $groupConversation->conversation->makeHidden(['created_at', 'updated_at', 'conversation_hash', 'is_group_value']);
        return $this->success($groupConversation, null, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/conversation/group/store",
     *     summary="Create a new group conversation",
     *     description="Creates a new group chat with the authenticated user as admin and adds selected users. Supports optional group avatar upload.",
     *     tags={"Group Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(type="object",
     *                 
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     description="Group name",
     *                     example="Laravel Devs"
     *                 ),
     *                 @OA\Property(
     *                     property="users[]",
     *                     type="array",
     *                     @OA\Items(type="integer",example=2),
     *                     description="List of user IDs to be added to the group"
     *                 ),
     *                 @OA\Property(
     *                     property="privacy_type",
     *                     type="integer",
     *                     description="Privacy type of the group (0: public, 1: private, 2: public need to admin approval)",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="avatar",
     *                     type="string",
     *                     format="binary",
     *                     description="Optional group profile image"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Group conversation created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="string", example=null),
     *             @OA\Property(property="message", type="string", example="گروه با موفقیت ایجاد شد")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطا در اعتبارسنجی داده‌ها")
     *         )
     *     ),
     * @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید")
     *         )
     *     ),
     * )
     */
    public function storeGroup(GroupConversationRequest $request, ImageService $imageService)
    {
        try {
            DB::beginTransaction();
            $authUser = auth()->user();
            $users = $request->users; // users to be add to group

            // add admin to group
            $users[] = $authUser->id;

            // meke a conversation_hash
            $conversationHash = generateConversationHash($users);

            $conversation = Conversation::create([
                'conversation_hash' => $conversationHash,
                'is_group' => 1, // چون گروه چت است
                'privacy_type' => $request->privacy_type,

            ]);

            foreach ($users as $userId) {
                ConversationUser::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $userId,
                    'is_admin' => ($userId == $authUser->id) ? 1 : 2, // authUser will be group admin
                    'status' => 1, // active
                    'joined_at' => now(),
                ]);
            }
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $imageService->setExclusiveDirectory('groups' . DIRECTORY_SEPARATOR . 'avatar' . DIRECTORY_SEPARATOR . $conversation->id);

                $avatarPath = $imageService->save($request->file('avatar'));
            }
            GroupConversation::create([
                'conversation_id' => $conversation->id,
                'name' => $request->name,
                'group_profile_avatar' => $avatarPath,
                'is_admin_only' => $request->is_private_group == 1 ? 1 : 2,
                'admin_user_id' => $authUser->id
            ]);
            DB::commit();
            return $this->success(null, 'گروه با موفقیت ایجاد شد', 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error();
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/conversation/group/leave/{groupConversation}",
     *     summary="Leave a group conversation",
     *     description="Allows the authenticated user to leave a group conversation. If the user is the main admin, the role will be transferred. If no members remain after the leave, the group will be deleted.",
     *     operationId="leaveGroupConversation",
     *     tags={"Group Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="groupConversation",
     *         in="path",
     *         required=true,
     *         description="ID of the group conversation to leave",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successfully left the group (or group deleted if no members remain)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="شما با موفقیت گروه را ترک کردید"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="User is not a member of the group or not authorized",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما عضو این مکالمه نیستید"),
     *         )
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *   @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *    @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید")
     *         )
     *     ),
     * )
     */
    public function leaveGroup(GroupConversation $groupConversation, ImageService $imageService)
    {
        $conversation = $groupConversation->conversation;
        $this->checkConversationMembership($conversation);
        try {
            DB::beginTransaction();

            $authUser = auth()->user();

            // استفاده از یک رابطه بدون شرط برای بروزرسانی pivot
            $conversation->users()->newPivotStatementForId($authUser->id)
                ->update(['left_at' => now(), 'is_admin' => 2]);

            // بررسی اگر کاربر ادمین اصلی گروه باشد
            if ($groupConversation->admin_user_id === auth()->id()) {
                $otherAdmins = $conversation->users()
                    ->wherePivot('is_admin', 1)
                    ->where('users.id', '!=', $authUser->id)
                    ->orderBy('conversation_user.joined_at')
                    ->get();

                if ($otherAdmins->isNotEmpty()) {
                    $groupConversation->admin_user_id = $otherAdmins->first()->id;
                } else {
                    $firstRemainingMember = DB::table('conversation_user')
                        ->where('conversation_id', $conversation->id)
                        ->whereNull('left_at')
                        ->where('user_id', '!=', $authUser->id)
                        ->orderBy('joined_at')
                        ->first();

                    if ($firstRemainingMember) {
                        // ارتقاء به ادمین
                        ConversationUser::where('conversation_id', $conversation->id)
                            ->where('user_id', $firstRemainingMember->user_id)
                            ->update(['is_admin' => 1]);

                        $groupConversation->admin_user_id = $firstRemainingMember->user_id;
                    }
                }

                $groupConversation->save();
            }


            $remainingUsersCount = ConversationUser::where('conversation_id', $conversation->id)
                ->whereNull('left_at')
                ->count();

            if ($remainingUsersCount === 0) {
                if (!empty($groupConversation->group_profile_avatar)) {
                    $imageService->deleteImage($groupConversation->group_profile_avatar);
                }

                $groupConversation->delete();
                $conversation->delete();

                DB::commit();
                return $this->success(null, 'شما از گروه خارج شدید و گروه به علت خالی بودن حذف گردید');
            }

            DB::commit();
            Log::info(ConversationUser::where('user_id', $authUser->id)->where('conversation_id', $conversation->id)->first());

            return $this->success(null, 'شما با موفقیت از گروه خارج شدید');

        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->error();
        }
    }



    /**
     * @OA\Post(
     *     path="/api/conversation/group/add-member/{groupConversation}",
     *     summary="Add members to a group conversation",
     *     description="Allows the group admin to add one or more users to a group chat. Newly added users will be set as regular members.",
     *     operationId="addMembersToGroup",
     *     tags={"Group Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="groupConversation",
     *         in="path",
     *         required=true,
     *         description="ID of the group conversation",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"users"},
     *             @OA\Property(
     *                 property="users",
     *                 type="array",
     *                 @OA\Items(type="integer", example=4),
     *                 description="An array of user IDs to be added to the group"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Members added successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="اعضا با موفقیت به گروه افزوده شدند"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *   @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *    @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید")
     *         )
     *     ),
     * )
     */
    public function addToGroup(GroupConversation $groupConversation, GroupConversationRequest $request)
    {
        $conversation = $groupConversation->conversation;
        $this->checkGroupAdmin($conversation);
        try {
            $users = $request->users;
            foreach ($users as $user) {
                ConversationUser::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $user,
                    'is_admin' => 2,
                    'status' => 1, // active
                    'joined_at' => now(),
                ]);
            }
            return $this->success(null, 'اعضا با موفقیت به گروه افزوده شدند', 201);
        } catch (Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/conversation/group/transfer-ownership/{groupConversation}",
     *     summary="Transfer group ownership to another user",
     *     description="Transfers the ownership of a group chat to another member. The current owner will be demoted to a regular member and the new user will be set as the group admin.",
     *     operationId="transferGroupOwnership",
     *     tags={"Group Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="groupConversation",
     *         in="path",
     *         required=true,
     *         description="ID of the group conversation",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id"},
     *             @OA\Property(
     *                 property="user_id",
     *                 type="integer",
     *                 description="The user ID to transfer ownership to",
     *                 example=12
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Ownership transferred successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="مالکیت گروه با موفقیت انتقال یافت"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Only the main admin can transfer ownership",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما مجوز انجام این عملیات را ندارید"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *   @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *    @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید")
     *         )
     *     )
     * )
     */
    public function transferOwnership(GroupConversation $groupConversation, GroupConversationRequest $request)
    {
        $this->checkMainAdmin($groupConversation);
        try {
            DB::beginTransaction();
            $conversation = $groupConversation->conversation;
            $authUser = auth()->user();
            $groupConversation->update([
                'admin_user_id' => $request->user_id
            ]);
            $conversation->users()->updateExistingPivot($authUser->id, ['is_admin' => 2]);
            $conversation->users()->updateExistingPivot($request->user_id, ['is_admin' => 1]);
            DB::commit();
            return $this->success(null, 'مالکیت گروه با موفقیت انتقال یافت');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error();
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/conversation/group/remove-avatar/{groupConversation}",
     *     summary="Remove group profile avatar",
     *     description="Removes the profile avatar of the group chat. Only the main admin is authorized to perform this action.",
     *     operationId="removeGroupAvatar",
     *     tags={"Group Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="groupConversation",
     *         in="path",
     *         required=true,
     *         description="ID of the group conversation",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Group avatar removed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="تصویر پروفایل گروه با موفقیت حذف شد"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Only the main admin can remove the avatar",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما مجوز انجام این عملیات را ندارید"),
     *         )
     *     ),
     *
     *      @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *   @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *    @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید")
     *         )
     *     ),
     * )
     */
    public function removeGroupAvatar(GroupConversation $groupConversation, ImageService $imageService)
    {
        $this->checkMainAdmin($groupConversation);
        try {
            if ($groupConversation->group_profile_avatar) {
                $imageService->deleteImage($groupConversation->group_profile_avatar);
            }
            $groupConversation->update([
                'group_profile_avatar' => null
            ]);
            return $this->success(null, 'تصویر پروفایل گروه با موفقیت حذف شد');
        } catch (Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Post(
     *     path="/api/conversation/group/change-avatar/{groupConversation}",
     *     summary="Change group profile avatar",
     *     description="Uploads a new profile avatar for the group chat. Only the main admin is authorized to perform this action. If a previous avatar exists, it will be deleted.",
     *     operationId="changeGroupAvatar",
     *     tags={"Group Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="groupConversation",
     *         in="path",
     *         required=true,
     *         description="ID of the group conversation",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"avatar"},
     *                 @OA\Property(
     *                     property="avatar",
     *                     type="file",
     *                     description="New profile image to upload"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group avatar updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="تصویر پروفایل گروه با موفقیت بروزرسانی شد"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Only the main admin can change the avatar",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما مجوز انجام این عملیات را ندارید"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - No image provided or invalid file format",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="فیلد آواتار الزامی است"),
     *             @OA\Property(property="data", type="object", example=null)
     *         )
     *     ),
     *
     *      @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *   @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *    @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید")
     *         )
     *     ),
     * )
     */
    public function changeGroupAvatar(GroupConversation $groupConversation, GroupConversationRequest $request, ImageService $imageService)
    {
        $this->checkMainAdmin($groupConversation);
        try {
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                if ($groupConversation->group_profile_avatar) {
                    $imageService->deleteImage($groupConversation->group_profile_avatar);
                }
                $imageService->setExclusiveDirectory('groups' . DIRECTORY_SEPARATOR . 'avatar' . DIRECTORY_SEPARATOR . $groupConversation->conversation->id);
                $avatarPath = $imageService->save($request->file('avatar'));
            }
            $groupConversation->update([
                'group_profile_avatar' => $avatarPath
            ]);
            return $this->success(null, 'تصویر پروفایل گروه با موفقیت بروزرسانی شد');
        } catch (Exception $e) {
            return $this->error();
        }
    }


    /**
     * @OA\Delete(
     *     path="/api/conversation/group/delete/{groupConversation}",
     *     summary="Delete a group conversation",
     *     description="Deletes the specified group conversation. Only the main admin of the group is authorized to perform this action.",
     *     operationId="deleteGroupConversation",
     *     tags={"Group Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="groupConversation",
     *         in="path",
     *         required=true,
     *         description="ID of the group conversation to delete",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group conversation deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="گروه چت با موفقیت حذف شد"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Only the main admin can delete the group",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما مجوز انجام این عملیات را ندارید"),
     *         )
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *   @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *    @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید")
     *         )
     *     )
     * )
     */
    public function deleteGroup(GroupConversation $groupConversation)
    {
        $this->checkMainAdmin($groupConversation);
        try {
            $authUser = auth()->user();
            $groupConversation->delete();
            return $this->success(null, 'گروه چت با موفقیت حذف شد');
        } catch (Exception $e) {
            return $this->error();
        }
    }


}
