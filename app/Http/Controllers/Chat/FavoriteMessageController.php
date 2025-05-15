<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\FavoriteMessage;
use App\Models\Message;
use App\Traits\ApiResponseTrait;
use App\Traits\GroupConversationTrait;
use Exception;
use Illuminate\Http\Request;
use Log;

class FavoriteMessageController extends Controller
{
    use ApiResponseTrait, GroupConversationTrait;

        /**
     * @OA\Get(
     *     path="/api/message/get-favorites",
     *     summary="Get favorite messages of the authenticated user",
     *     description="Returns a paginated list of messages that the authenticated user has marked as favorite.",
     *     operationId="getFavoriteMessages",
     *     tags={"Message","Favorite Message"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of favorite messages returned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",ref="#/components/schemas/Message"),
     *                     
     *             ),
     *             
     *             @OA\Property(property="next_page_url", type="string", example="https://yourdomain.com/api/messages/favorites?page=2"),
     *             @OA\Property(property="prev_page_url", type="string", nullable=true),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="current_page", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     * )
     */
    public function favoriteMessages()
    {
        $userId =auth()->id();
        $favorites = FavoriteMessage::where('user_id',$userId)->simplePaginate(15)
        ->each(function($favorite){
            $favorite->message;
            $favorite->makeHidden(['updated_at','deleted_at']);
        });

        return $this->success($favorites);
    }

    /**
     * @OA\Post(
     *     path="/api/message/toggle-favorite/{message}",
     *     summary="Toggle favorite status for a message",
     *     description="Adds or removes the specified message to/from the authenticated user's favorites. If the message is already favorited, it will be removed; otherwise, it will be added.",
     *     operationId="toggleFavoriteMessage",
     *     tags={"Message", "Favorite Message"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="message",
     *         in="path",
     *         description="ID of the message to toggle favorite status",
     *         required=true,
     *         @OA\Schema(type="integer", example=42)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Favorite status toggled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="پیام به لیست علاقمندی ها اضافه شد")
     *         )
     *     ),
     *
     * @OA\Response(
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
     *     @OA\Response(
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
    public function toggleFavorite(Message $message)
    {
        $this->checkConversationMembership($message->conversation);
        try {
            $user = auth()->user();
            $favorite = FavoriteMessage::where('user_id', $user->id)
                ->where('message_id', $message->id)
                ->first();

            if ($favorite) {
                $favorite->delete();
                return $this->success('پیام از لیست علاقمندی ها پاک شد');
            } else {
                FavoriteMessage::create([
                    'user_id' => $user->id,
                    'message_id' => $message->id,
                ]);
                return $this->success('پیام به لیست علاقمندی ها اضافه شد');
            }
        } catch (Exception $e) {
            return $this->error();
        }
    }

    

}
