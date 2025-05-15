<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema(
 *     schema="FavoriteMessage",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="creation datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="update datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="deleted_at", type="string", format="datetime",description="delete datetime", example="2025-02-22T14:30:00Z"),
 *  *     @OA\Property(property="user", type="object",
 *       @OA\Property(property="id", type="integer", example=2),
 *       @OA\Property(property="username", type="string", example="محمد001"),
 * ),
 *     @OA\Property(property="conversation", type="object",
 *       @OA\Property(property="id", type="integer", example=2),
 *       @OA\Property(property="is_group_value", type="string", description="is group: 'yes' if 1, 'no' if 2", example="بله"),
 *       @OA\Property(property="group", type="object",
 *          @OA\Property(property="id", type="integer", example=2),
 *          @OA\Property(property="name", type="string", example="اخراجی ها"),
 *       )
 *       )
 * ),
 *     @OA\Property(property="sender", type="object",
 *       @OA\Property(property="id", type="integer", example=2),
 *       @OA\Property(property="username", type="string", example="محمد001"),
 *       @OA\Property(property="profile_photo_path", type="string", example="path/avatar.jpg"),
 * ),
 * @OA\Property(property="media", type="object",
 *       @OA\Property(property="id", type="integer", example=2),
 *       @OA\Property(property="file_name", type="string", example="file name"),
 *       @OA\Property(property="file_path", type="string", example="path/file.format"),
 *       @OA\Property(property="mime_type", type="string", example="image/jpeg"),
 * )
 * )
 */
class FavoriteMessage extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = ['user_id', 'message_id'];

    protected $hidden = ['user_id', 'message_id'];
   
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
