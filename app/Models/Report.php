<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @OA\Schema(
 *     schema="Report",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="reason", type="string", example="سلام. محتوای این پیام خلاف شئون اخلاقی است. لطفا پیگیری فرمائید. با تشکر"),
 *     @OA\Property(property="admin_comment", type="string", example="گزارش پیگیری شد و به کاربر تذکر داده شد"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="creation datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="update datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="deleted_at", type="string", format="datetime",description="delete datetime", example="2025-02-22T14:30:00Z"),
 *     @OA\Property(property="status_value", type="string", description="status value: 'pending' if 0, 'approved' if 1, 'rejected' if 2", example="رد شده"),
 *      @OA\Property(property="message", type="object",
 *          @OA\Property(property="id", type="integer", example=1),
 *          @OA\Property(property="content", type="string", example="سلام. چطوری؟"),
 *          @OA\Property(property="sent_at", type="string", format="date-time", description="sent datetime", example="2025-02-22T10:00:00Z"),
 *          @OA\Property(property="message_type_value", type="string", description="message type: 'text' if 0, 'multimedia' if 1, 'mixed' if 2", example="پیام متنی"),
 *          @OA\Property(property="conversation", type="object",
 *              @OA\Property(property="id", type="integer", example=2),
 *              @OA\Property(property="is_group_value", type="string", description="is group: 'yes' if 1, 'no' if 2", example="بله"),
 *              @OA\Property(property="group", type="object",
 *                  @OA\Property(property="id", type="integer", example=2),
 *                  @OA\Property(property="name", type="string", example="اخراجی ها"),
 *                  @OA\Property(property="owner", type="object",
 *                      @OA\Property(property="id", type="integer", example=2),
 *                      @OA\Property(property="username", type="string", example="ایمان"),
 *                      @OA\Property(property="first_name", type="string", example="ایمان"),
 *                      @OA\Property(property="last_name", type="string", example="مدائنی"),
 *                    )
 *                )
 *          )
 *     ),
 *     @OA\Property(property="sender", type="object",
 *       @OA\Property(property="id", type="integer", example=2),
 *       @OA\Property(property="username", type="string", example="محمد001"),
 *       @OA\Property(property="profile_photo_path", type="string", example="path/avatar.jpg"),
 * ),
 *    
 * @OA\Property(property="medias", type="object",
 *       @OA\Property(property="id", type="integer", example=2),
 *       @OA\Property(property="file_name", type="string", example="file name"),
 *       @OA\Property(property="file_path", type="string", example="path/file.format"),
 *       @OA\Property(property="mime_type", type="string", example="image/jpeg"),
 * ),
 * )
 */
class Report extends Model
{
    use HasFactory;
    protected $fillable = ['message_id', 'user_id', 'reason', 'status', 'admin_comment'];

    protected $hidden = ['message_id', 'user_id', 'status'];

    protected $appends = ['status_value'];
    public function getStatusValueAttribute()
    {
        switch ($this->status) {
            case 0:
                $result = 'در حال بررسی';
                break;
            case 1:
                $result = 'تأیید شده';
                break;
            case 2:
                $result = 'رد شده';
                break;
        }
        return $result;
    }
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
