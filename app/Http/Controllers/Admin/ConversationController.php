<?php

namespace App\Http\Controllers\Admin;

use App\Events\UserTypingEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConversationRequest;
use App\Http\Services\Chat\ConversationService;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\Message;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    use ApiResponseTrait;

    protected $conversationService;
    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }
    public function listConversations(Request $request)
    {
        $conversations = Conversation::with(['users', 'messages'])->paginate(15);
        return $this->success($conversations);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/conversation/show/{conversation}",
     *     summary="Get a specific conversation by ID",
     *     description="Returns the details of a conversation only if the authenticated user is an admin.",
     *     tags={"Admin - Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="ID of the conversation to retrieve",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Conversation found and returned",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Conversation"),
     *             @OA\Property(property="message", type="string", example=null)
     *         )
     *     ),
     *
     *      @OA\Response(
     *         response=403,
     *         description="User is not an admin.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما مجوز انجام این عملیات را ندارید")
     *         ),
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
     *     )),
     *   @OA\Response(
     *         response=500,
     *         description="internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *     ))
     * )
     */
    public function show(Conversation $conversation)
    {
        return $this->conversationService->show($conversation);
    }


    /**
     * @OA\Post(
     *     path="/api/admin/conversation/join/{conversation}",
     *     summary="Join a conversation as admin",
     *     description="Allows a user to join a conversation as an admin. The user can only join if the conversation is not a group conversation and if they are not already a member of the conversation.",
     *     tags={"Admin - Conversation"},
     *     security={{"bearerAuth": {}}},
     *    @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="ID of the conversation to retrieve",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User successfully joined the conversation as an admin.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="شما می توانید در این مکالمه شرکت کنید"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="User is already a member of the conversation.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما قبلا عضو این مکالمه هستید")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="User is not allowed to join a group conversation.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما مجوز ورود به این مکالمه را ندارید")
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
     *   @OA\Response(
     *         response=500,
     *         description="internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *     ))
     * )
     */
    public function joinConversationAsAdmin(Conversation $conversation)
    {
        try {
            $userId = auth()->id();
            if ($conversation->groupConversation) {
                return $this->error('شما مجوز ورود به این مکالمه را ندارید', 403);
            }
            $isMembership = $conversation->users()->where('user_id', $userId)->wherePivot('left_at', null)
                ->exists();
            if ($isMembership) {
                return $this->error('شما قبلا عضو این مکالمه هستید', 400);
            }
            ConversationUser::create([
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
                'joined_at' => now(),
                'is_admin' => 2
            ]);
            return $this->success(null, 'شما میتوانید در این مکالمه شرکت کنید', 201);
        } catch (Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Post(
     *     path="/api/admin/conversation/store/{targetUser}",
     *     summary="Start a new private conversation with a specific user",
     *     description="Creates a new one-on-one conversation between the authenticated user and the specified target user. Prevents duplicate or self conversations. only admins can do this method",
     *     tags={"Admin - Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="targetUser",
     *         in="path",
     *         required=true,
     *         description="The ID of the user you want to start a conversation with",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Conversation successfully created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="مکالمه جدید ایجاد شد"),
     *             @OA\Property(property="data", type="string", example=null)
     *             
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request (duplicate conversation or self-conversation)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="مکالمه تکراری است یا نمی‌توانید با خودتان مکالمه داشته باشید")
     *         )
     *     ),
     *   @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *  @OA\Response(
     *         response=403,
     *         description="User is not an admin.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما مجوز انجام این عملیات را ندارید")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *     @OA\Response(
     *         response=500,
     *         description="internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *     ))
     * )
     */
    public function store(User $targetUser)
    {
        return $this->conversationService->store($targetUser);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/conversation/search/{conversation}",
     *     summary="Search messages in a conversation",
     *     description="Returns a list of messages in the conversation that contain the given search term only if the authenticated user is an admin.",
     *     tags={"Admin - Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="ID of the conversation to search messages in",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *    @OA\Parameter(
     *        name="search",
     *        in="query",
     *        required=false,
     *        @OA\Schema(type="string")
     *    ),
     *     @OA\Response(
     *         response=200,
     *         description="Messages matching the search term",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="message", ref="#/components/schemas/Message"),
     *                    
     *                 )
     *             ),
     *            @OA\Property(property="message", type="string", example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="User is not an admin.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما مجوز انجام این عملیات را ندارید")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *     @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     * )
     */
    public function search(Conversation $conversation, ConversationRequest $request)
    {
        return $this->conversationService->search($conversation, $request);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/conversation/last-seen-message/{conversation}",
     *     summary="Get last seen message ID by the admin",
     *     description="Returns the ID of the last message seen by the authenticated user that is an admin.",
     *     tags={"Admin - Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="ID of the conversation",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Last seen message ID retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="integer", example=150),
     *             @OA\Property(property="message", type="string", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="User is not an admin.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما مجوز انجام این عملیات را ندارید")
     *         ),
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
     *      @OA\Response(
     *         response=500,
     *         description="internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *     ))
     * )
     */
    public function getLastSeenMessage(Conversation $conversation)
    {
        return $this->conversationService->getLastSeenMessage($conversation);
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/conversation/{conversation}/update-last-seen-message/{message}",
     *     summary="Update last seen message of a conversation for admin",
     *     description="Updates the ID of the last message seen by the admin.",
     *     tags={"Admin - Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="ID of the conversation",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="message",
     *         in="path",
     *         required=true,
     *         description="ID of the message to set as last seen",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Last seen message updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="string", example=null),
     *             @OA\Property(property="message", type="string", example="آخرین پیام دیده‌شده با موفقیت بروزرسانی شد")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="User is not an admin.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما مجوز انجام این عملیات را ندارید")
     *         ),
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
     *       @OA\Response(
     *         response=500,
     *         description="internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *     ))
     * )
     */
    public function updateLastSeen(Conversation $conversation, Message $message)
    {
        $conversationUser = $conversation->users()
            ->where('user_id', auth()->id())
            ->wherePivot('left_at', null)
            ->first();

        if (!$conversationUser) {
            return $this->error('شما هنوز عضو این مکالمه نیستید یا دسترسی ندارید', 403);
        }
        return $this->conversationService->updateLastSeen($conversation, $message);
    }

}
