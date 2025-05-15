<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageReactionRequest;
use App\Http\Services\Chat\MessageService;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Traits\ApiResponseTrait;
use App\Traits\GroupConversationTrait;
use Exception;
use Illuminate\Http\Request;
use Log;

class MessageReactionController extends Controller
{
    use ApiResponseTrait, GroupConversationTrait;

    protected $messageService;
    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }
    /**
     * @OA\Get(
     *     path=" api/admin/message/get-reactions/{message}",
     *     summary="Get grouped emoji reactions for a message",
     *     description="Returns a grouped list of emoji reactions for a specific message in a conversation. Each group contains the emoji, total count, and users who reacted with it.",
     *     tags={"Admin - Message","Admin - Reaction"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="message",
     *         in="path",
     *         required=true,
     *         description="ID of the message",
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of emoji reactions grouped by emoji",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="emoji", type="string", example="👍"),
     *                 @OA\Property(property="count", type="integer", example=3),
     *                 @OA\Property(
     *                     property="users",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="username", type="string", example="johndoe")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *        @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *   @OA\Response(
     *         response=403,
     *         description="The user is not authorized to delete this message.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما عضو این مکالمه نیستید")
     *         )
     *     ),
     *   @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     ))
     * )
     */
    public function messageReactions(Message $message)
    {
        return $this->messageService->messageReactions($message);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/message/toggle-react/{message}",
     *     summary="Toggle emoji reaction for a message",
     *     description="Adds or removes an emoji reaction for a specific message by the admin. If the emoji already exists, it will be removed; otherwise, it will be added.",
     *     tags={"Admin - Message","Admin - Reaction"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="message",
     *         in="path",
     *         required=true,
     *         description="ID of the message to react to",
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"emoji"},
     *             @OA\Property(
     *                 property="emoji",
     *                 type="string",
     *                 maxLength=10,
     *                 example="😂",
     *                 description="Emoji to toggle for the message"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Emoji toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="string", example="😂"),
     *             @OA\Property(property="message", type="string", example="ایموجی به پیام افزوده شد")
     *         )
     *     ),
     *    @OA\Response(
     *         response=403,
     *         description="The user is not authorized to delete this message.",
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
     *   @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     * @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="وارد کردن ایموجی الزامی است")
     *         )
     *     ),
     *    
     * )
     */
    public function toggleReaction(Message $message,MessageReactionRequest $request)
    {
        $this->checkConversationMembership($message->conversation);
        $this->checkSystemAdminCan($message);
        return $this->messageService->toggleReaction($request,$message);
    }

}
