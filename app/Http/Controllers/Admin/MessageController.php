<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageRequest;
use App\Http\Services\Chat\MessageService;
use App\Http\Services\File\FileService;
use App\Jobs\InitializeMessagesStatusesJobForAdmin;
use App\Models\Conversation;
use App\Models\Media;
use App\Models\Message;
use App\Models\MessageUserStatus;
use App\Traits\ApiResponseTrait;
use App\Traits\GroupConversationTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    use ApiResponseTrait, GroupConversationTrait;

    protected $messageService;
    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/message/get/{conversation}",
     *     summary="Get Messages from a Conversation",
     *     description="Retrieve messages from a specific conversation. Only admins of the conversation can retrieve messages.",
     *     security={{"bearerAuth":{}}},
     *     tags={"Admin - Message"},
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         description="The conversation ID",
     *         @OA\Schema(type="integer"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Messages successfully retrieved.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status",
     *                 type="boolean",
     *                 example=true,
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="null",
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     ref="#/components/schemas/Message"
     *                    
     *                 ),
     *             ),
     *         ),
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
     *     )
     * )
     */
    public function getMessages(Conversation $conversation)
    {
        dispatch(new InitializeMessagesStatusesJobForAdmin($conversation, auth()->user()));
        return $this->messageService->getMessages($conversation);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/message/media/download/{media}",
     *     summary="Download a media file",
     *     description="This endpoint allows the user to download a media file if they have the proper permissions. If the user does not have admin permission, a 403 error will be returned.",
     *     tags={"Admin - Message"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="media",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the media to be downloaded."
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="The media file is being downloaded.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="null"),
     *             @OA\Property(property="data", type="string", example="path/file.format"),
     * 
     *         )
     *     ),
     *      @OA\Response(
     *         response=403,
     *         description="User is not an admin.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما مجوز انجام این عملیات را ندارید")
     *         ),
     *     ),
     *       @OA\Response(
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
     * )
     */
    public function downloadMedia(Media $media)
    {
        return $this->messageService->downloadMedia($media);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/message/send/{conversation}",
     *     summary="Send a message in a conversation by admin",
     *     description="Send a message to a conversation `only by admin`. If a media file is uploaded, the message type will be updated accordingly. The admins can not send message to group conversations",
     *     tags={"Admin - Message"},
     *     security={{"bearerAuth":{}}}, 
     *     @OA\Parameter(
     *         name="conversation",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the conversation."
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="parent_id",
     *                     type="integer",
     *                     description="The Id of the parent message.This field is optional",
     *                      example="52"
     *                 ),
     *                 @OA\Property(
     *                     property="content",
     *                     type="string",
     *                     description="The content of the message (either text or media)."
     *                 ),
     *                @OA\Property(
     *                     property="media[]",
     *                     type="array",
     *                     @OA\Items(
     *                         type="string",
     *                         format="binary"
     *                     ),
     *                description="Upload a single media file."
     *)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="The message has been successfully sent.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="پیام شما با موفقیت ارسال شد")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected error occurred while sending the message.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطایی غیرمنتظره در سرور رخ داده است. لطفا دوباره تلاش کنید")
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
     *     ))
     * )
     */
    public function sendMessage(Conversation $conversation, MessageRequest $request, FileService $fileService)
    {
        if (Gate::denies('send', $conversation)) {
            return $this->error('امکان ارسال پیام به این مکالمه برای شما وجود ندارد', 403);
        }
        return $this->messageService->sendMessage($conversation, $request, $fileService);
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/message/mark-as-read/{message}",
     *     summary="Mark a message as read",
     *     description="This endpoint allows the admin to mark a message as read. The user's message status will be updated to 'read' and the timestamp of when it was read will be recorded.Only admins of the conversation can do this and they ca not access to this method if conversation is group",
     *     tags={"Admin - Message"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="message",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the message to be marked as read."
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="The message has been successfully marked as read.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="پیام به عنوان خوانده شده ثبت شد")
     *         )
     *     ),
     *    @OA\Response(
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
     * )
     */
    public function markAsRead(Message $message)
    {
        if (Gate::denies('markAsRead', $message)) {
            return $this->error('شما عضو این مکالمه نیستید یا دسترسی امکان پذیر نیست', 403);
        }
        return $this->messageService->markAsRead($message);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/message/reply-to-message/{message}",
     *     summary="Reply to a specific message",
     *     description="This endpoint allows an authenticated user to reply to an existing message in a conversation they are a member of. The reply can include text content and/or media files.",
     *     tags={"Admin - Message"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="message",
     *         in="path",
     *         required=true,
     *         description="The ID of the message being replied to",
     *         @OA\Schema(type="integer", example=456)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"content"},
     *                 @OA\Property(
     *                     property="content",
     *                     type="string",
     *                     description="Text content of the reply message",
     *                     example="Sure, I'll take care of it."
     *                 ),
     *                 @OA\Property(
     *                     property="media[]",
     *                     type="array",
     *                     @OA\Items(
     *                         type="string",
     *                         format="binary"
     *                     ),
     *                description="Upload a single media file."
     *)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Message successfully sent as a reply.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="true"),
     *             @OA\Property(property="message", type="string", example="پاسخ شما با موفقیت ارسال شد"),
     *             @OA\Property(property="data", type="string", example="null")
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
     *  @OA\Response(
     *         response=403,
     *         description="The user is not authorized.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما عضو این مکالمه نیستید")
     *         )
     *     ),
     * @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *    
     *   @OA\Response(
     *         response=422,
     *         description="Validation error (e.g., missing fields)",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="false"),
     *             @OA\Property(property="message", type="string", example="فیلد مکالمه الزامی است")
     *         )
     *     ),
     *
     *  @OA\Response(
     *         response=500,
     *         description="internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *     ))
     * )
     */
    public function replyToMessage(Message $message, MessageRequest $request, FileService $fileService)
    {
        return $this->messageService->replyToMessage($message, $request, $fileService);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/message/forward/{message}",
     *     summary="Forward a message to one or multiple conversations",
     *     description="This endpoint allows an authenticated user to forward an existing message (text or media) to one or more conversations they are a member of.",
     *     tags={"Admin - Message"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="message",
     *         in="path",
     *         required=true,
     *         description="The ID of the message to forward",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *             @OA\Property(
     *                 property="conversations",
     *                 type="array",
     *                 description="List of conversation IDs to forward the message to",
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *  )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Message successfully forwarded to selected conversations.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="true"),
     *             @OA\Property(property="message", type="string", example="پیام با موفقیت فوروارد شد"),
     *             @OA\Property(property="data", type="string", example="null")
     *          )
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *  @OA\Response(
     *         response=403,
     *         description="The user is not authorized.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما عضو این مکالمه نیستید")
     *         )
     *     ),
     * @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *    
     *   @OA\Response(
     *         response=422,
     *         description="Validation error (e.g., missing fields)",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="false"),
     *             @OA\Property(property="message", type="string", example="فیلد مکالمه الزامی است")
     *         )
     *     ),
     *
     *  @OA\Response(
     *         response=500,
     *         description="internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *     ))
     * )
     */
    public function forwardMessage(Message $message, MessageRequest $request)
    {
        $conversationIds = $request->conversations;
        return $this->messageService->forwardMessage($message, $conversationIds);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/message/delete/{message}",
     *     summary="Delete a message",
     *     description="This endpoint allows the sender to delete a message. If the sender is not the authenticated user, a 403 error will be returned. The message and its associated media will be permanently deleted.",
     *     tags={"Admin - Message"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="message",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the message to be deleted."
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="The message has been successfully deleted.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="پیام با موفقیت حذف شد")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="The user is not authorized.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما نمی‌توانید این پیام را حذف کنید")
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
     * )
     */
    public function deleteMessage(Message $message)
    {
        return $this->messageService->deleteMessage($message);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/message/delete-for-user/{message}",
     *     summary="Delete message for a specific user",
     *     description="This endpoint allows the user to delete a message for themselves. The message will be removed from the user's view without affecting other users.",
     *     tags={"Admin - Message"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="message",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the message to be deleted for the user."
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="The message has been successfully deleted for the user.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="پیام برای شما حذف شد")
     *         )
     *     ),
     *   @OA\Response(
     *         response=403,
     *         description="The user is not authorized.",
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
     * )
     */
    public function deleteMessageForUser(Message $message)
    {
        $this->checkConversationMembership($message->conversation);
        return $this->messageService->deleteMessageForUser($message);
    }

     /**
     * @OA\Post(
     *     path="/api/admin/message/reply-privately/{message}",
     *     summary="Send a private message in reply to a group message",
     *     description="This endpoint allows a user to reply privately to the sender of a group message. A new private conversation will be created if it does not already exist.",
     *     tags={"Admin - Message"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="message",
     *         in="path",
     *         required=true,
     *         description="The ID of the original group message to reply to",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *           mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"content"},
     *                 @OA\Property(
     *                     property="content",
     *                     type="string",
     *                     description="The content of the message (either text or media)."
     *                 ),
     *                @OA\Property(
     *                     property="media[]",
     *                     type="array",
     *                     @OA\Items(
     *                         type="string",
     *                         format="binary"
     *                     ),
     *                description="Upload a single media file."
     *             )
     *           )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Private message sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="true"),
     *             @OA\Property(property="message", type="string", example="پیام خصوصی با موفقیت ارسال شد"),
     *             @OA\Property(property="data", type="string", example="null")
     *          )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Access denied (e.g., sender not in the group)",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="false"),
     *             @OA\Property(property="message", type="string", example="امکان ارسال پیام خصوصی وجود ندارد")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (e.g., missing fields)",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="false"),
     *             @OA\Property(property="message", type="string", example="فیلد محتوا الزامی است")
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
     * @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید")
     *         )
     *     )
     * )
     */
    public function sendPrivateMessage(Message $message, MessageRequest $request, FileService $fileService)
    {
        return $this->messageService->sendPrivateMessage($message, $request, $fileService);

    }

    /**
     * @OA\Patch(
     *     path="/api/admin/message/update/{message}",
     *     summary="Update a message",
     *     description="This endpoint allows the user to update the content of a message if it has not been read by all users. If the message has been read by all users, it cannot be updated.",
     *     tags={"Admin - Message"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="message",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the message to be updated."
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="content",
     *                     type="string",
     *                     description="The new content of the message."
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="The message has been successfully updated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="پیام شما با موفقیت ویرایش شد")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="The message cannot be updated because it has been read by all users.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما نمی‌توانید این پیام را ویرایش کنید چون توسط همه کاربران خوانده شده است")
     *         )
     *     ),
     *  @OA\Response(
     *         response=403,
     *         description="User is not a member of the conversation.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="هر کاربر تنها میتواند پیام های ارسالی خودش را ویرایش کند")
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
     * )
     */
    public function updateMessage(Message $message, MessageRequest $request)
    {
        return $this->messageService->updateMessage($message, $request);
    }

}
