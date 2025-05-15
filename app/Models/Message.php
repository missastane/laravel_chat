<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Log;

/**
 * @OA\Schema(
 *     schema="Message",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="content", type="string", example="سلام. چطوری؟"),
 *     @OA\Property(property="sent_at", type="string", format="date-time", description="sent datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="creation datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="update datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="deleted_at", type="string", format="datetime",description="delete datetime", example="2025-02-22T14:30:00Z"),
 *     @OA\Property(property="message_type_value", type="string", description="message type: 'text' if 0, 'multimedia' if 1, 'mixed' if 2", example="پیام متنی"),
 *     @OA\Property(property="conversation", type="object",
 *       @OA\Property(property="id", type="integer", example=2),
 *       @OA\Property(property="is_group_value", type="string", description="is group: 'yes' if 1, 'no' if 2", example="بله"),
 *       @OA\Property(property="group", type="object",
 *          @OA\Property(property="id", type="integer", example=2),
 *          @OA\Property(property="name", type="string", example="اخراجی ها"),
 *          @OA\Property(property="owner", type="object",
 *             @OA\Property(property="id", type="integer", example=2),
 *             @OA\Property(property="username", type="string", example="ایمان"),
 *             @OA\Property(property="first_name", type="string", example="ایمان"),
 *             @OA\Property(property="last_name", type="string", example="مدائنی"),
 *       )
 *       )
 * ),
 *     @OA\Property(property="sender", type="object",
 *       @OA\Property(property="id", type="integer", example=2),
 *       @OA\Property(property="username", type="string", example="محمد001"),
 *       @OA\Property(property="profile_photo_path", type="string", example="path/avatar.jpg"),
 * ),
 *      @OA\Property(property="parent", type="object",
 *       @OA\Property(property="id", type="integer", example=2),
 *       @OA\Property(property="content", type="string", example="سلام"),
 * ),
 * @OA\Property(property="medias", type="object",
 *       @OA\Property(property="id", type="integer", example=2),
 *       @OA\Property(property="file_name", type="string", example="file name"),
 *       @OA\Property(property="file_path", type="string", example="path/file.format"),
 *       @OA\Property(property="mime_type", type="string", example="image/jpeg"),
 * ),
 * )
 */
class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable =
        [
            'conversation_id',
            'sender_id',
            'content',
            'message_type',
            'sent_at',
            'delivered_at',
            'read_at',
            'parent_id',
            'private_reply_message_id',
            'forwarded_message_id'
        ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }
    protected $hidden = ['conversation_id', 'sender_id', 'message_type', 'parent_id','private_reply_message_id','forwarded_message_id'];
    protected $appends = ['message_type_value', 'conversation', 'sender', 'medias', 'parent'];

    public function getMessageTypeValueAttribute()
    {
        switch ($this->message_type) {
            case 0:
                $result = 'پیام متنی';
                break;
            case 1:
                $result = 'میام مالتی مدیا';
                break;
            case 2:
                $result = 'پیام متنی و مالتی مدیا';
                break;
        }
        return $result;

    }
    public function getSenderAttribute()
    {
        $sender = $this->sender()->first(['id', 'username', 'profile_photo_path']);
        $sender->makeHidden(['activation_value', 'user_type_value', 'is_discoverable_value','is_blocked_value']);
        return $sender;
    }
    public function getParentAttribute()
    {
        if (!is_null($this->parent_id)) {
            $parent = $this->parent()->first(['id', 'content', 'created_at', 'sender_id']);
            return $parent;
        }
        return null;
    }

    public function getConversationAttribute()
    {
        $conversation = $this->getRelationValue('conversation');

        if ($conversation) {
            $conversation->makeHidden('privacy_type_value', 'conversation_hash', 'created_at', 'updated_at');
        }

        return $conversation;
    }
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }


    public function parent()
    {
        return $this->belongsTo(Message::class, 'parent_id')->with('parent');
    }
    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function getMediasAttribute()
    {
        $media = $this->media()->first(['id', 'file_name', 'file_path', 'mime_type']);
        return $media;
    }
    public function media()
    {
        return $this->hasMany(Media::class, 'message_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function pinnedMessages()
    {
        return $this->hasMany(PinnedMessage::class);
    }

    public function favoritedBy()
    {
        return $this->hasMany(FavoriteMessage::class);
    }
}
