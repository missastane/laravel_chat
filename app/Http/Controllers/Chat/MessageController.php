<?php

namespace App\Http\Controllers\Chat;

use App\Events\NewMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\MessageRequest;
use App\Http\Services\Chat\MessageDeliveryService;
use App\Http\Services\Chat\MessageService;
use App\Http\Services\File\FileService;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\Media;
use App\Models\Message;
use App\Models\MessageUserStatus;
use App\Models\Report;
use App\Traits\ApiResponseTrait;
use App\Traits\GroupConversationTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Log;
use Storage;


class MessageController extends Controller
{
    use ApiResponseTrait, GroupConversationTrait;
    protected $messageService;
    protected $messageDeliveryService;
    public function __construct(MessageService $messageService, MessageDeliveryService $messageDeliveryService)
    {
        $this->messageService = $messageService;
        $this->messageDeliveryService = $messageDeliveryService;
    }
    /**
     * @OA\Post(
     *     path="/api/message/send/{conversation}",
     *     summary="Send a message in a conversation",
     *     description="Send a message to a conversation. If a media file is uploaded, the message type will be updated accordingly.",
     *     tags={"Message"},
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
     *         description="The user is not a member of the conversation.",
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
     *     ))
     * )
     */
    public function sendMessage(Conversation $conversation, MessageRequest $request, FileService $fileService)
    {
        $this->checkConversationMembership($conversation);
        return $this->messageService->sendMessage($conversation, $request, $fileService);
    }

    /**
     * @OA\Get(
     *     path="/api/message/get/{conversation}",
     *     summary="Get Messages from a Conversation",
     *     description="Retrieve messages from a specific conversation. Only members of the conversation can retrieve messages.",
     *     operationId="getMessages",
     *     security={{"bearerAuth":{}}},
     *     tags={"Message"},
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
     *         description="User is not a member of the conversation.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما عضو این مکالمه نیستید")
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
        $this->checkConversationMembership($conversation);
        return $this->messageService->getMessages($conversation);
    }

    /**
     * @OA\Patch(
     *     path="/api/message/mark-as-read/{message}",
     *     summary="Mark a message as read",
     *     description="This endpoint allows the user to mark a message as read. The user's message status will be updated to 'read' and the timestamp of when it was read will be recorded.Only members of the conversation can do this",
     *     operationId="markMessageAsRead",
     *     tags={"Message"},
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
        $this->checkConversationMembership($message->conversation);
        return $this->messageService->markAsRead($message);
    }

    /**
     * @OA\Patch(
     *     path="/api/message/update/{message}",
     *     summary="Update a message",
     *     description="This endpoint allows the user to update the content of a message if it has not been read by all users. If the message has been read by all users, it cannot be updated.",
     *     operationId="updateMessage",
     *     tags={"Message"},
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
        return $this->messageService->updateMessage($message,$request);
    }

    /**
     * @OA\Get(
     *     path="/api/message/media/download/{media}",
     *     summary="Download a media file",
     *     description="This endpoint allows the user to download a media file if they have the proper permissions. If the user does not have permission, a 403 error will be returned.",
     *     operationId="downloadMedia",
     *     tags={"Media","Message"},
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
     *     @OA\Response(
     *         response=403,
     *         description="You do not have permission to download this file.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما اجازه دسترسی به این فایل را ندارید")
     *         )
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
        if (Gate::denies('canDownload', $media)) {

            return $this->error('شما اجازه دسترسی به این فایل را ندارید', 403);
        }
        return $this->messageService->downloadMedia($media);
    }

    /**
     * @OA\Delete(
     *     path="/api/message/delete/{message}",
     *     summary="Delete a message",
     *     description="This endpoint allows the sender to delete a message. If the sender is not the authenticated user, a 403 error will be returned. The message and its associated media will be permanently deleted.",
     *     operationId="deleteMessage",
     *     tags={"Message"},
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
     *     path="/api/message/delete-for-user/{message}",
     *     summary="Delete message for a specific user",
     *     description="This endpoint allows the user to delete a message for themselves. The message will be removed from the user's view without affecting other users.",
     *     operationId="deleteMessageForUser",
     *     tags={"Message"},
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
     *     path="/api/message/report/{message}",
     *     summary="Report a message",
     *     description="This endpoint allows the user to report a message with a reason. The message will be reported for review.",
     *     operationId="reportMessage",
     *     tags={"Message"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="message",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the message to be reported."
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"reason"},
     *                 @OA\Property(
     *                     property="reason",
     *                     type="string",
     *                     description="The reason for reporting the message.",
     *                     example="This message contains inappropriate content."
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="The message has been successfully reported.",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="پیام با موفقیت گزارش شد")
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
    public function reportMessage(Message $message, MessageRequest $request)
    {
        $this->checkConversationMembership($message->conversation);
        try {
            $user = auth()->user();
            Report::create([
                'message_id' => $message->id,
                'user_id' => $user->id,
                'reason' => $request->reason,
            ]);
            return $this->success(null, 'پیام با موفقیت گزارش شد', 201);

        } catch (Exception $e) {
            return $this->error();
        }
    }


    /**
     * @OA\Post(
     *     path="/api/message/reply-privately/{message}",
     *     summary="Send a private message in reply to a group message",
     *     description="This endpoint allows a user to reply privately to the sender of a group message. A new private conversation will be created if it does not already exist.",
     *     operationId="sendPrivateMessageFromGroupMessage",
     *     tags={"Message"},
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
    public function sendPrivateMessageFromGroupMessage(Message $message, MessageRequest $request, FileService $fileService)
    {
        return $this->messageService->sendPrivateMessage($message,$request,$fileService);

    }

    /**
     * @OA\Post(
     *     path="/api/message/reply-to-message/{message}",
     *     summary="Reply to a specific message",
     *     description="This endpoint allows an authenticated user to reply to an existing message in a conversation they are a member of. The reply can include text content and/or media files.",
     *     tags={"Message"},
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
        $this->checkConversationMembership($message->conversation);
        return $this->messageService->replyToMessage($message, $request, $fileService);
    }

    /**
     * @OA\Post(
     *     path="/api/message/forward/{message}",
     *     summary="Forward a message to one or multiple conversations",
     *     description="This endpoint allows an authenticated user to forward an existing message (text or media) to one or more conversations they are a member of.",
     *     tags={"Message"},
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
        $this->checkConversationMembership($message->conversation);
        $conversationIds = $request->conversations;
        return $this->messageService->forwardMessage($message, $conversationIds);
    }

}
