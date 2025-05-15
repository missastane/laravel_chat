<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PinnedMessageRequest;
use App\Http\Services\Chat\MessageService;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\PinnedMessage;
use App\Traits\ApiResponseTrait;
use App\Traits\GroupConversationTrait;
use Exception;
use Illuminate\Http\Request;

class PinnedMessageController extends Controller
{
    use ApiResponseTrait, GroupConversationTrait;
    protected $messageService;
    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }
    /**
     * @OA\Get(
     *     path="/api/admin/message/get-pinned/{conversation}",
     *     summary="Get pinned message of a conversation",
     *     description="Returns the pinned message of the specified conversation. It can be either a public pinned message or a private one, depending on the user's role and visibility.",
     *     tags={"Admin - Message","Admin - Pinned Message"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="ID of the conversation",
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Pinned message retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-03T14:00:00Z"),
     *                 @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",ref="#/components/schemas/Message"),
     *                     
     *             ),
     * )
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
     *     ))
     * )
     */
    public function pinnedMessage(Conversation $conversation)
    {
       return $this->messageService->pinnedMessage($conversation);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/message/toggle-pin/{message}",
     *     summary="Toggle pinned status of a message",
     *     description="Pins or unpins a message for the current user in private conversations, or publicly in group conversations if the user is the main admin.",
     *     tags={"Admin - Message","Admin - Pinned Message"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="message",
     *         in="path",
     *         required=true,
     *         description="ID of the message to be pinned or unpinned",
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Message pin toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="پیام برای شما پین شد")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Message pinned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="پیام به صورت عمومی پین شد")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="The user is not authorized to delete this message.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما عضو این مکالمه نیستید")
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
     * @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید")
     *         )
     *     )
     *    
     * )
     */
    public function togglePin(Message $message)
    {
        $this->checkConversationMembership($message->conversation);
        return $this->messageService->togglePin($message);
      
    }




}
