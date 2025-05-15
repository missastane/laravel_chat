<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="JoinRequest",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="creation datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="update datetime", example="2025-02-22T10:00:00Z"),
 *     @OA\Property(property="status_value", type="string", example="درخواست معلق"),
 *      @OA\Property(property="group", type="object",
 *       @OA\Property(property="id", type="integer", example=2),
 *       @OA\Property(property="name", type="string", example="ایمان"),
 *       @OA\Property(property="owner", type="object",
 *          @OA\Property(property="id", type="integer", example=2),
 *          @OA\Property(property="username", type="string", example="ایمان"),
 *          @OA\Property(property="first_name", type="string", example="ایمان"),
 *          @OA\Property(property="last_name", type="string", example="مدائنی"),
 *       )
 *       ),
 *     @OA\Property(property="user", type="object",
 *       @OA\Property(property="id", type="integer", example=2),
 *       @OA\Property(property="username", type="string", example="محمد001"),
 *       @OA\Property(property="first_name", type="string", example="محمد"),
 *       @OA\Property(property="last_name", type="string", example="اردوخانی"),
 * ),
 * )
 */
class JoinRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'status'
    ];

    protected $appends = ['status_value', 'group', 'user'];
    protected $hidden = [
        'conversation_id',
        'user_id',
        'status'
    ];
    public function getStatusValueAttribute()
    {
        switch ($this->status) {
            case 1:
                $result = 'درخواست پذیرفته شده';
                break;
            case 2:
                $result = 'درخواست رد شده';
                break;
            case 3:
                $result = 'درخواست معلق';
                break;
        }
        return $result;
    }

    public function getUserAttribute()
    {
        $user = $this->user()->first(['id', 'username', 'first_name', 'last_name']);
        $user->makeHidden(['activation_value', 'user_type_value', 'is_discoverable_value']);
        return $user;
    }

    public function getGroupAttribute()
    {
        $group = $this->groupConversation()->with('owner:id,username,first_name,last_name')->first(['id', 'name', 'admin_user_id']);
        $group->makeHidden('is_admin_only_value');
        $group->owner->makeHidden(['activation_value', 'user_type_value', 'is_discoverable_value']);
        return $group;
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function groupOwner()
    {
        return $this->groupConversation()->get()->owner;
    }
    public function groupConversation()
    {
        return GroupConversation::with('owner')->where('conversation_id', $this->conversation_id)->first();
    }
}
