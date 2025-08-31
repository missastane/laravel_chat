<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @OA\Schema(
 *     schema="Conversation",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="conversation_hash", type="string", example="djfhugbskdsdou9gjfdphgbh"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="creation datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="update datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="is_group_value", type="string", description="is group: 'yes' if 1, 'no' if 2", example="بله"),
 *     @OA\Property(property="privacy_type_value", type="string", description="privacy type status: 'privacy' if 0, 'public' if 1, 'required to approve' if 2", example="خصوصی"),
 *    @OA\Property(property="group", type="object",
 *       @OA\Property(property="id", type="integer", example=2),
 *       @OA\Property(property="name", type="string", example="ایمان"),
 *       @OA\Property(property="owner", type="object",
 *          @OA\Property(property="id", type="integer", example=2),
 *          @OA\Property(property="username", type="string", example="ایمان"),
 *          @OA\Property(property="first_name", type="string", example="ایمان"),
 *          @OA\Property(property="last_name", type="string", example="مدائنی"),
 *       )
 *       ),
 * )
 */
class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_hash',
        'is_group',
        'privacy_type'

    ];
    protected $hidden = ['privacy_type', 'is_group'];
    protected $appends = [
        'is_group_value',
        'privacy_type_value',
        'group'
    ];

    public function getIsGroupValueAttribute()
    {
        if ($this->is_group == 1) {
            return 'بله';
        } else {
            return 'خیر';
        }
    }
    public function getPrivacyTypeValueAttribute()
    {
        if ($this->status == 0) {
            return 'خصوصی';
        } elseif ($this->status == 1) {
            return 'عمومی';
        } else {
            return 'عمومی نیازمند به تایید ادمین';
        }
    }

    public function getGroupAttribute()
    {
        $group = $this->groupConversation()->with('owner:id,username,first_name,last_name')->first(['id', 'name', 'admin_user_id','group_profile_avatar']);
        if ($group) {
            $group->makeHidden('is_admin_only_value');
            $group->owner->makeHidden(['activation_value', 'user_type_value', 'is_discoverable_value','is_blocked_value']);
        }

        return $group;
    }
    public function isBlockedFor(User $user): bool
    {
        return Block::where('blocker_id', $user->id)
            ->where('blockable_type', self::class)
            ->where('blockable_id', $this->id)
            ->exists();
    }
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function requests()
    {
        return $this->hasMany(JoinRequest::class);
    }
    public function groupConversation()
    {
        return $this->hasOne(GroupConversation::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_user', 'conversation_id', 'user_id')
            ->wherePivotNull('deleted_at')->wherePivotNull('left_at');
    }

    public function pinnedMessage()
    {
        return $this->hasMany(PinnedMessage::class)->latest();
    }
}
