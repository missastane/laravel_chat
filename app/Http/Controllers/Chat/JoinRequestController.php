<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\GroupConversation;
use App\Models\JoinRequest;
use App\Traits\ApiResponseTrait;
use App\Traits\GroupConversationTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;

class JoinRequestController extends Controller
{
    use ApiResponseTrait, GroupConversationTrait;

    /**
     * @OA\Get(
     *     path="/api/conversation/join/pending-requests/{groupConversation}",
     *     summary="Get pending join requests",
     *     description="Returns `a list of pending join requests (status = 3) for a specific group` conversation. Only accessible by the group admin.",
     *     tags={"Group Conversation","Join Request"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="groupConversation",
     *         in="path",
     *         required=true,
     *         description="ID of the group conversation",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of pending join requests",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="null"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                    ref="#/components/schemas/JoinRequest"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="User is not authorized (not the group admin)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما مجوز انجام این عملیات را ندارید"),
     *         )
     *     ),
     *   @OA\Response(
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
     *     ))
     * )
     */
    public function pendingJoinRequests(GroupConversation $groupConversation)
    {
        $this->checkMainAdmin($groupConversation);
        $pendingRequests = $groupConversation->conversation->requests()->where('status', 3) ->get();
        return $this->success($pendingRequests);
    }

    /**
     * @OA\Post(
     *     path="/api/conversation/join/{groupConversation}",
     *     summary="Request to join a group conversation",
     *     description="Allows an authenticated user to request joining a group conversation.If the user is already a member or has a pending request, a new request will not be created.If the group is public, the request will be automatically approved and the user will be added immediately.",
     *     tags={"Group Conversation","Join Request"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="groupConversation",
     *         in="path",
     *         required=true,
     *         description="ID of the groupConversation",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Join request created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="درخواست با موفقیت ثبت شد"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="User is already a member or has a pending request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما قبلا عضو این مکالمه هستید"),
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
     *     )
     * )
     */
    public function joinConversation(GroupConversation $groupConversation)
    {
        try {
            DB::beginTransaction();
            $user = auth()->user();
            // if user is a member
            $isMember = $groupConversation->conversation->users()->where('user_id', auth()->id())->where('left_at', null)->exists();

            if ($isMember) {
                return $this->error('شما قبلاً عضو این مکالمه هستید', 400);
            }

            $isMemberLeftGroup = ConversationUser::where('conversation_id',$groupConversation->conversation_id)
            ->where('user_id',$user->id)
            ->whereNotNull('left_at')->first();
            if($isMemberLeftGroup){
                $isMemberLeftGroup->delete();
            }

            // if user already has request
            $existing = JoinRequest::where('conversation_id', $groupConversation->conversation_id)
                ->where('user_id', $user->id)
                ->where('status', 3) // pending
                ->first();

            if ($existing) {
                return $this->error('درخواست شما در حال بررسی است', 400);
            }

            $is_public_conversation = $groupConversation->conversation->privacy_type == 1;
            $status = $is_public_conversation ? 1 : 3;
            $joinRequest = JoinRequest::create([
                'conversation_id' => $groupConversation->conversation_id,
                'user_id' => $user->id,
                'status' => $status, // pending or approve
            ]);
            $joinRequest->refresh();
            if ($joinRequest->status == 1) {
                ConversationUser::create([
                    'conversation_id' => $groupConversation->conversation_id,
                    'user_id' => $joinRequest->user_id,
                    'joined_at' => now()
                ]);
            }
            DB::commit();
            return $this->success(null, 'درخواست با موفقیت ثبت شد', 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error();
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/conversation/join/respond-to-request/{joinRequest}",
     *     summary="Respond to a join request",
     *     description="Allows the main admin of a group conversation to approve or reject a user's join request.",
     *     tags={"Group Conversation","Join Request"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="joinRequest",
     *         in="path",
     *         required=true,
     *         description="ID of the join request",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="integer", enum={1,2}, example=1, description="1 to approve, 2 to reject")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Join request successfully responded to",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="درخواست تایید شد"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="وارد کردن استاتوس الزامی است"),
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
     *     )
     * )
     */
    public function respondToJoinRequest(JoinRequest $joinRequest, Request $request)
    {
        $request->validate([
            'status' => 'required|in:1,2', // 1: approve, 2: reject
        ]);
        $this->checkMainAdmin($joinRequest->conversation->groupConversation);
        try {
            DB::beginTransaction();
            $user = auth()->user();
            $joinRequest->update([
                'status' => $request->status,
            ]);

            if ($request->status == 1) {
                $joinRequest->conversation->users()->attach($joinRequest->user_id);
            }
            DB::commit();
            $message = $request->status == 1 ? 'درخواست تأیید شد' : 'درخواست رد شد';
            return $this->success(null, $message);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error();
        }
    }
}
