<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema(
 *     schema="GroupConversation",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="گروه دوستانه"),
 *     @OA\Property(property="group_profile_avatar", type="string", example="path/image.jpg"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="creation datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="update datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="is_admin_only_value", type="string", description="is group: 'yes' if 1, 'no' if 2", example="بله"),
 *     @OA\Property(property="conversation", type="object",
 *        @OA\Property(property="id", type="integer", example=2),
 *        @OA\Property(property="privacy_type_value", type="string", example="خصوصی"),
 * ),
 *     @OA\Property(property="owner", type="object",
 *        @OA\Property(property="id", type="integer", example=2),
 *        @OA\Property(property="username", type="string", example="mehdi007"),
 *        @OA\Property(property="first_name", type="string", example="مهدی"),
 *        @OA\Property(property="last_name", type="string", example="مولایی"),
 * ),
 * )
 */
class GroupConversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'name',
        'group_profile_avatar',
        'is_admin_only',
        'admin_user_id'
    ];

    protected $hidden = ['conversation_id','is_admin_only','admin_user_id'];
    protected $appends = ['is_admin_only_value'];
    public function getIsAdminOnlyValueAttribute()
    {
        if ($this->status == 1) {
            return 'بله';
        } else {
            return 'خیر';
        }
    }

    public function conversation() 
    {
        return $this->belongsTo(Conversation::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class,'admin_user_id','id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class,'conversation_user')->withPivot('is_admin');
    }
   
    public function blockedByUsers()
    {
        return $this->morphMany(Block::class, 'blockable');
    }
}
