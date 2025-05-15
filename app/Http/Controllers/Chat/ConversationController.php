<?php

namespace App\Http\Controllers\Chat;

use App\Events\UserOnlineEvent;
use App\Events\UserTypingEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConversationRequest;
use App\Http\Services\Chat\ConversationService;
use App\Models\ArchivedConversation;
use App\Models\Conversation;
use App\Models\ConversationRole;
use App\Models\ConversationUser;
use App\Models\Message;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ValidatesConversationContext;
use Illuminate\Support\Facades\DB;
class ConversationController extends Controller
{
    use ValidatesConversationContext, ApiResponseTrait;
    protected $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }

    /**
     * @OA\Get(
     *     path="/api/conversation",
     *     summary="Get all conversations of the authenticated user",
     *     description="Returns a list of all conversations that the currently authenticated user is part of.",
     *     tags={"Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of conversations retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Conversation")),
     *             @OA\Property(property="message", type="string", example=null)
     *        
     *  )
     *     ),
     *   @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     * )
     */
    public function index()
    {
        $userId = Auth::id();
        $conversations = Conversation::whereHas('users', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->get();

        return $this->success($conversations);
    }

    /**
     * @OA\Post(
     *     path="/api/conversation/store/{targetUser}",
     *     summary="Start a new private conversation with a specific user",
     *     description="Creates a new one-on-one conversation between the authenticated user and the specified target user. Prevents duplicate or self conversations.",
     *     tags={"Conversation"},
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
        $this->conversationService->store($targetUser);
    }


    /**
     * @OA\Get(
     *     path="/api/conversation/show/{conversation}",
     *     summary="Get a specific conversation by ID",
     *     description="Returns the details of a conversation only if the authenticated user is a member of that conversation.",
     *     tags={"Conversation"},
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
     *     @OA\Response(
     *         response=403,
     *         description="The user is not a member of the conversation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما عضو این مکالمه نیستید")
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
        $this->conversationService->show($conversation);
    }

    /**
     * @OA\Delete(
     *     path="/api/conversation/delete/{conversation}",
     *     summary="Delete a conversation for the authenticated user",
     *     description="Removes the authenticated user from the specified conversation. This does not delete the conversation for other members.",
     *     tags={"Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="ID of the conversation to leave",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Conversation successfully removed for the user",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="مکالمه برای شما حذف شد"),
     *             @OA\Property(property="data", type="string", example=null)
     * )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="User does not have access to this conversation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما عضو این مکالمه نیستید")
     *         )
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
     *   @OA\Response(
     *         response=500,
     *         description="internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *     ))
     * )
     */
    public function destroy(Conversation $conversation)
    {
        $this->validateUserAndMessageInConversation($conversation, null);
        try {
            $userId = Auth::id();

            // delete user from conversation
            ConversationUser::where('conversation_id', $conversation->id)
                ->where('user_id', $userId)
                ->delete();

            return $this->success(null, 'مکالمه برای شما حذف شد');
        } catch (Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Get(
     *     path="/api/conversation/search/{conversation}",
     *     summary="Search messages in a conversation",
     *     description="Returns a list of messages in the conversation that contain the given search term.",
     *     tags={"Conversation"},
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
     *         description="User does not have access to this conversation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما عضو این مکالمه نیستید")
     *         )
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
        $this->validateUserAndMessageInConversation($conversation, null);
        $this->conversationService->search($conversation, $request);
    }

    /**
     * @OA\Patch(
     *     path="/api/conversation/toggle-archive/{conversation}",
     *     summary="Toggle archive state of a conversation",
     *     description="Archive or unarchive a conversation for the authenticated user.",
     *     tags={"Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="ID of the conversation to archive or unarchive",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Archive state toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="مکالمه با موفقیت آرشیو شد"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="User does not have access to this conversation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما عضو این مکالمه نیستید")
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
     *      @OA\Response(
     *         response=500,
     *         description="internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *     ))
     * )
     */
    public function toggleArchive(Conversation $conversation)
    {
        $this->validateUserAndMessageInConversation($conversation, null);
        try {
            $user = auth()->user();
            $conversationUser = ConversationUser::where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->first();
            $conversationUser->update([
                'is_archived' => $conversationUser->is_archived == 2 ? 1 : 2,
            ]);
            $is_archived = $conversationUser->refresh()->is_archived;
            $message = $is_archived == 1
                ? 'مکالمه با موفقیت آرشیو شد'
                : 'مکالمه با موفقیت از حالت آرشیو خارج شد';
            return $this->success(null, $message);
        } catch (Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/conversation/toggle-mute/{conversation}",
     *     summary="Toggle mute state of a conversation",
     *     description="Archive or mute a conversation for the authenticated user.",
     *     tags={"Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="ID of the conversation to mute or unmute",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mute state toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="مکالمه با موفقیت به حالت بیصدا درآمد"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="User does not have access to this conversation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما عضو این مکالمه نیستید")
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
     *      @OA\Response(
     *         response=500,
     *         description="internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *     ))
     * )
     */
    public function toggleMute(Conversation $conversation)
    {
        $this->validateUserAndMessageInConversation($conversation, null);
        try {
            $user = auth()->user();
            $conversationUser = ConversationUser::where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->first();
            $conversationUser->update([
                'is_muted' => $conversationUser->is_muted == 2 ? 1 : 2,
            ]);
            $isMuted = $conversationUser->refresh()->is_muted;
            $message = $isMuted == 1
                ? 'مکالمه با موفقیت به حالت بیصدا درآمد'
                : 'مکالمه با موفقیت از حالت بیصدا خارج شد';
            return $this->success(null, $message);
        } catch (Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/conversation/toggle-favorite/{conversation}",
     *     summary="Toggle favorite state of a conversation",
     *     description="Favorite or mute a conversation for the authenticated user.",
     *     tags={"Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="ID of the conversation to favorite or not",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Favorite state toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="مکالمه با موفقیت به لیست علاقمندی ها اضافه شد"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="User does not have access to this conversation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما عضو این مکالمه نیستید")
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
     *      @OA\Response(
     *         response=500,
     *         description="internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *     ))
     * )
     */
    public function toggleFavorite(Conversation $conversation)
    {
        $this->validateUserAndMessageInConversation($conversation, null);
        try {
            $user = auth()->user();
            $conversationUser = ConversationUser::where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->first();
            $conversationUser->update([
                'is_favorite' => $conversationUser->is_favorite == 2 ? 1 : 2,
            ]);
            $is_favorite = $conversationUser->refresh()->is_favorite;
            $message = $is_favorite == 1
                ? 'مکالمه با موفقیت به لیست علاقمندی ها اضافه شد'
                : 'مکالمه با موفقیت از لیست علاقمندی ها پاک شد';
            return $this->success(null, $message);
        } catch (\Exception $e) {
            return $this->error();
        }
    }


    /**
     * @OA\Patch(
     *     path="/api/conversation/toggle-pin/{conversation}",
     *     summary="Toggle pin state of a conversation",
     *     description="Pin or mute a conversation for the authenticated user.",
     *     tags={"Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="ID of the conversation to pin or unpin",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pin state toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="مکالمه با موفقیت پین شد"),
     *             @OA\Property(property="data", type="string", example=null),
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="User does not have access to this conversation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما عضو این مکالمه نیستید")
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
     *      @OA\Response(
     *         response=500,
     *         description="internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *     ))
     * )
     */
    public function togglePin(Conversation $conversation)
    {
        $this->validateUserAndMessageInConversation($conversation, null);
        try {
            $user = auth()->user();
            $conversationUser = ConversationUser::where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->first();
            $conversationUser->update([
                'is_pinned' => $conversationUser->is_pinned == 2 ? 1 : 2,
            ]);
            $is_pinned = $conversationUser->refresh()->is_pinned;
            $message = $is_pinned == 1
                ? 'مکالمه با موفقیت پین شد'
                : 'مکالمه با موفقیت از حالت پین خارج شد';
            return $this->success(null, $message);
        } catch (Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Get(
     *     path="/api/conversation/last-seen-message/{conversation}",
     *     summary="Get last seen message ID by the authenticated user",
     *     description="Returns the ID of the last message seen by the authenticated user in the specified conversation.",
     *     tags={"Conversation"},
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
     *         description="User is not a member of the conversation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما عضو این مکالمه نیستید")
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
        $this->validateUserAndMessageInConversation($conversation, null);
        $this->conversationService->getLastSeenMessage($conversation);
    }


    /**
     * @OA\Patch(
     *     path="/api/conversation/{conversation}/update-last-seen-message/{message}",
     *     summary="Update last seen message of a conversation for the authenticated user",
     *     description="Updates the ID of the last message seen by the authenticated user in a specific conversation.",
     *     tags={"Conversation"},
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
     *         description="User is not authorized for this conversation or message",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما عضو این مکالمه نیستید")
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
        $this->validateUserAndMessageInConversation($conversation, $message);
        $this->conversationService->updateLastSeen($conversation, $message);
    }

    /**
     * @OA\Post(
     *     path="/api/conversation/block/{conversation}",
     *     summary="Block a conversation",
     *     description="`Blocks a conversation for the authenticated user`. Prevents the user from receiving further messages from this conversation. Returns an error if the conversation is already blocked.",
     *     tags={"Conversation"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="ID of the conversation to block",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Conversation successfully blocked",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="مکالمه با موفقیت بلاک شد"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Conversation already blocked",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما قبلاً این مکالمه را بلاک کرده‌اید"),
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
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید")
     *         )
     *     ),
     * )
     */
    public function blockConversation(Conversation $conversation)
    {
        $this->validateUserAndMessageInConversation($conversation);
        try {
            $authUser = auth()->user();
            $group = $conversation->groupConversation;
            if ($group && $group->owner == $authUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'شما مالک این گروه هستید و نمیتوانید آن را بلاک کنید'
                ], 403);
            }
            $alreadyBlocked = $authUser->blocks()
                ->where('blockable_id', $conversation->id)
                ->where('blockable_type', Conversation::class)
                ->exists();

            if ($alreadyBlocked) {
                return $this->error('شما قبلاً این مکالمه را بلاک کرده‌اید', 400);
            }
            $authUser->blocks()->create([
                'blockable_id' => $conversation->id,
                'blockable_type' => Conversation::class,
                'blocker_id' => $authUser->id,
            ]);
            return $this->success(null, 'مکالمه با موفقیت بلاک شد');
        } catch (Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/conversation/unblock/{conversation}",
     *     summary="Unblock a previously blocked conversation",
     *     description="Allows an authenticated user to unblock a conversation they have previously blocked.",
     *     tags={"Conversation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="ID of the conversation to unblock",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Conversation successfully blocked",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="مکالمه با موفقیت از حالت بلاک خارج شد"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Conversation not blocked",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="این مکالمه قبلاً بلاک نشده است"),
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
     *    
     * )
     */
    public function unblockConversation(Conversation $conversation)
    {
        try {
            $authUser = auth()->user();

            $blocked = $authUser->blocks()
                ->where('blockable_id', $conversation->id)
                ->where('blockable_type', Conversation::class)
                ->first();

            if (!$blocked) {
                return $this->error('این مکالمه قبلاً بلاک نشده است', 400);
            }

            $blocked->delete();

            return $this->success(null, 'مکالمه با موفقیت از بلاک خارج شد');
        } catch (Exception $e) {
            return $this->error();
        }
    }


}
